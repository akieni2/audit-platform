<?php

namespace App\Services\Questionnaires;

use App\Domain\Questionnaires\Events\EntretienResponsesRecorded;
use App\Domain\Questionnaires\Events\QuestionnaireSnapshotCaptured;
use App\Domain\Risk\Enums\RiskLifecycleStatus;
use App\Models\Entretien;
use App\Models\EntretienResponse;
use App\Models\IdentifiedRisk;
use App\Models\QuestionnaireTemplate;
use App\Services\Iam\SecurityAuditService;
use App\Services\Runtime\BusinessEventLogger;
use App\Services\Runtime\CoreTransactionRunner;
use App\Services\Runtime\RuntimeMetricsService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

final class QuestionnaireRuntimeService
{
    public const SNAPSHOT_SCHEMA_VERSION = 1;

    public function __construct(
        private SecurityAuditService $audit,
        private BusinessEventLogger $events,
        private RuntimeMetricsService $metrics,
        private CoreTransactionRunner $transactions,
    ) {}

    /**
     * @return array{
     *   template: object,
     *   existingResponses: Collection<int, EntretienResponse>,
     *   progressPercent: ?int,
     *   snapshot: array<string, mixed>
     * }
     */
    public function buildViewData(Entretien $entretien): array
    {
        $entretien->loadMissing('mission', 'service');
        $snapshot = $this->ensureSnapshot($entretien);
        if ($snapshot === []) {
            throw new InvalidArgumentException('Aucun template runtime disponible pour cet entretien.');
        }

        return [
            'template' => $this->templateViewModel($snapshot),
            'existingResponses' => $entretien->questionnaireResponses()->get()->keyBy('questionnaire_question_id'),
            'progressPercent' => $entretien->questionnaireCompletionPercent(),
            'snapshot' => $snapshot,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function ensureSnapshot(Entretien $entretien, bool $force = false): array
    {
        $snapshot = $this->readSnapshot($entretien);
        if (! $force && $snapshot !== []) {
            return $snapshot;
        }

        if ($entretien->questionnaire_template_id === null) {
            return [];
        }

        $entretien->loadMissing([
            'questionnaireTemplate.sections.questions' => fn ($q) => $q->where('active', true)->orderBy('sort_order'),
        ]);

        $template = $entretien->questionnaireTemplate;
        if (! $template instanceof QuestionnaireTemplate) {
            return [];
        }

        $snapshot = $this->compileSnapshot($template);
        $correlationId = $this->events->resolveCorrelationId([
            'mission_id' => $entretien->mission_id,
            'entretien_id' => $entretien->id,
        ]);

        $snapshotAttributes = [];
        if (Schema::hasColumn('entretiens', 'questionnaire_snapshot')) {
            $snapshotAttributes['questionnaire_snapshot'] = $snapshot;
        }
        if (Schema::hasColumn('entretiens', 'questionnaire_snapshot_version')) {
            $snapshotAttributes['questionnaire_snapshot_version'] = (int) ($template->version ?? 1);
        }
        if (Schema::hasColumn('entretiens', 'questionnaire_snapshot_hash')) {
            $snapshotAttributes['questionnaire_snapshot_hash'] = $snapshot['meta']['hash'] ?? null;
        }
        if (Schema::hasColumn('entretiens', 'questionnaire_snapshot_taken_at')) {
            $snapshotAttributes['questionnaire_snapshot_taken_at'] = now();
        }

        if ($snapshotAttributes !== []) {
            $entretien->forceFill($snapshotAttributes)->save();
        }

        $this->metrics->increment(
            metricKey: 'core_runtime.questionnaire.snapshot.captured',
            delta: 1,
            dimensions: ['template_version' => (string) ($template->version ?? 1)],
            scopeType: 'mission',
            scopeId: $entretien->mission_id,
        );
        $this->events->record(
            eventName: 'core_runtime.questionnaire.snapshot_captured',
            payload: [
                'entretien_id' => $entretien->id,
                'snapshot_hash' => $snapshot['meta']['hash'] ?? null,
                'template_id' => $template->id,
            ],
            context: ['correlation_id' => $correlationId],
            aggregateType: 'entretien',
            aggregateId: $entretien->id,
            missionId: $entretien->mission_id,
            correlationId: $correlationId,
            idempotencyKey: 'questionnaire-snapshot:'.$entretien->id.':'.($snapshot['meta']['hash'] ?? 'na'),
        );

        QuestionnaireSnapshotCaptured::dispatch($entretien->fresh(), $snapshot, $correlationId);

        return $snapshot;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{
     *   entretien: Entretien,
     *   response_ids: list<int>,
     *   identified_risk_ids: list<int>
     * }
     */
    public function recordResponses(
        Entretien $entretien,
        array $rows,
        ?\App\Models\User $user,
        Request $request,
    ): array {
        $snapshot = $this->ensureSnapshot($entretien);
        if ($snapshot === []) {
            throw new InvalidArgumentException('Impossible d’enregistrer des réponses sans snapshot de questionnaire.');
        }

        $allowedQuestions = $this->questionsById($snapshot);
        $allowedIds = array_map('intval', array_keys($allowedQuestions));

        $statusColumnAvailable = Schema::hasColumn('entretiens', 'status');
        $previousStatus = $statusColumnAvailable ? $entretien->status : null;
        $correlationId = $this->events->resolveCorrelationId([
            'mission_id' => $entretien->mission_id,
            'entretien_id' => $entretien->id,
        ]);

        $result = $this->transactions->run(
            name: 'questionnaire.record_responses',
            context: ['correlation_id' => $correlationId, 'entretien_id' => $entretien->id, 'mission_id' => $entretien->mission_id],
            callback: function ($transaction) use ($rows, $entretien, $user, $request, $allowedQuestions, $allowedIds, $statusColumnAvailable, $correlationId) {
                $responseIds = [];
                $identifiedRiskIds = [];

                foreach ($rows as $row) {
                    $qid = (int) ($row['questionnaire_question_id'] ?? 0);
                    if (! in_array($qid, $allowedIds, true)) {
                        throw new InvalidArgumentException('Question hors modèle attaché à l’entretien.');
                    }

                    $question = $allowedQuestions[$qid];
                    $payload = [
                        'answer_boolean' => array_key_exists('answer_boolean', $row) ? $row['answer_boolean'] : null,
                        'answer_text' => $row['answer_text'] ?? null,
                        'answer_json' => $row['answer_json'] ?? null,
                        'observation' => $row['observation'] ?? null,
                        'uploaded_documents_metadata' => $row['uploaded_documents_metadata'] ?? null,
                        'detected_risk' => $row['detected_risk'] ?? null,
                        'created_by' => $user?->id,
                    ];

                    $response = EntretienResponse::query()->updateOrCreate(
                        [
                            'entretien_id' => $entretien->id,
                            'questionnaire_question_id' => $qid,
                        ],
                        $payload
                    );

                    $responseIds[] = (int) $response->id;
                    $this->audit->entretienResponseCreated($user, $entretien, $response, $request);

                    $capturedRisk = $this->captureIdentifiedRisk(
                        $entretien,
                        $qid,
                        $question,
                        $row,
                        $user?->id,
                        $request,
                    );

                    if ($capturedRisk instanceof IdentifiedRisk) {
                        $identifiedRiskIds[] = (int) $capturedRisk->id;
                    }
                }

                $entretienUpdate = [];
                if ($statusColumnAvailable) {
                    $entretienUpdate['status'] = Entretien::STATUS_IN_PROGRESS;
                }
                if (Schema::hasColumn('entretiens', 'conducted_by')) {
                    $entretienUpdate['conducted_by'] = $user?->id;
                }
                if (Schema::hasColumn('entretiens', 'conducted_at')) {
                    $entretienUpdate['conducted_at'] = $entretien->conducted_at ?? now();
                }
                if ($entretienUpdate !== []) {
                    $entretien->update($entretienUpdate);
                }

                $freshEntretien = $entretien->fresh();
                $transaction->afterCommit(function () use ($freshEntretien, $responseIds, $identifiedRiskIds, $correlationId): void {
                    EntretienResponsesRecorded::dispatch(
                        $freshEntretien,
                        $responseIds,
                        $identifiedRiskIds,
                        $correlationId,
                    );
                });

                return [
                    'entretien' => $freshEntretien,
                    'response_ids' => $responseIds,
                    'identified_risk_ids' => $identifiedRiskIds,
                ];
            }
        );

        if ($statusColumnAvailable && in_array($previousStatus, [null, '', Entretien::STATUS_DRAFT], true)) {
            $this->audit->entretienStarted($user, $result['entretien'], $request);
        }

        $this->metrics->increment(
            metricKey: 'core_runtime.questionnaire.responses.recorded',
            delta: count($result['response_ids']),
            dimensions: ['identified_risks_count' => (string) count($result['identified_risk_ids'])],
            scopeType: 'mission',
            scopeId: $entretien->mission_id,
        );
        $this->events->record(
            eventName: 'core_runtime.questionnaire.responses_recorded',
            payload: [
                'entretien_id' => $result['entretien']->id,
                'response_ids' => $result['response_ids'],
                'identified_risk_ids' => $result['identified_risk_ids'],
            ],
            context: ['correlation_id' => $correlationId],
            aggregateType: 'entretien',
            aggregateId: $result['entretien']->id,
            actor: $user,
            missionId: $result['entretien']->mission_id,
            correlationId: $correlationId,
            idempotencyKey: 'questionnaire-responses:'.$result['entretien']->id.':'.sha1(json_encode($result['response_ids'])),
        );

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function readSnapshot(Entretien $entretien): array
    {
        $snapshot = $entretien->questionnaire_snapshot ?? null;

        return is_array($snapshot) ? $snapshot : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function compileSnapshot(QuestionnaireTemplate $template): array
    {
        $sections = $template->sections->map(function ($section) {
            return [
                'id' => (int) $section->id,
                'title' => (string) $section->title,
                'description' => $section->description,
                'sort_order' => (int) $section->sort_order,
                'questions' => $section->questions->map(function ($question) {
                    return [
                        'id' => (int) $question->id,
                        'code' => $question->code,
                        'question' => (string) $question->question,
                        'help_text' => $question->help_text,
                        'question_type' => (string) $question->question_type,
                        'required' => (bool) $question->required,
                        'allows_observation' => (bool) $question->allows_observation,
                        'allows_risk_detection' => (bool) $question->allows_risk_detection,
                        'expected_documents' => $question->expected_documents,
                        'risk_category' => $question->risk_category,
                        'risk_level' => $question->risk_level,
                        'sort_order' => (int) $question->sort_order,
                        'active' => (bool) $question->active,
                        'metadata' => $question->metadata ?? [],
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $payload = [
            'meta' => [
                'schema_version' => self::SNAPSHOT_SCHEMA_VERSION,
                'captured_at' => now()->toIso8601String(),
                'template_id' => (int) $template->id,
                'template_name' => (string) $template->name,
                'template_slug' => (string) $template->slug,
                'template_version' => (int) ($template->version ?? 1),
                'template_active' => (bool) $template->active,
            ],
            'template' => [
                'id' => (int) $template->id,
                'name' => (string) $template->name,
                'slug' => (string) $template->slug,
                'description' => $template->description,
                'mission_type' => $template->mission_type,
                'version' => (int) ($template->version ?? 1),
                'active' => (bool) $template->active,
                'department_scope' => $template->department_scope ?? [],
                'sections' => $sections,
            ],
        ];

        $payload['meta']['hash'] = sha1(json_encode($payload['template'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        return $payload;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function questionsById(array $snapshot): array
    {
        $questions = [];
        foreach (($snapshot['template']['sections'] ?? []) as $section) {
            foreach (($section['questions'] ?? []) as $question) {
                $questions[(int) $question['id']] = $question;
            }
        }

        return $questions;
    }

    private function templateViewModel(array $snapshot): object
    {
        /** @var object $viewModel */
        $viewModel = json_decode(json_encode($snapshot['template'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $viewModel;
    }

    /**
     * @param  array<string, mixed>  $question
     * @param  array<string, mixed>  $row
     */
    private function captureIdentifiedRisk(
        Entretien $entretien,
        int $questionId,
        array $question,
        array $row,
        ?int $userId,
        Request $request,
    ): ?IdentifiedRisk {
        $structuredRisk = $row['identified_risk'] ?? null;
        if (! is_array($structuredRisk) || ! ($question['allows_risk_detection'] ?? false)) {
            return null;
        }

        $title = trim((string) ($structuredRisk['title'] ?? ''));
        if ($title === '') {
            return null;
        }

        $signature = sha1(implode('|', [
            (string) $entretien->id,
            (string) $questionId,
            mb_strtolower($title),
        ]));

        $risk = IdentifiedRisk::query()->firstOrNew([
            'source_signature' => $signature,
        ]);

        $risk->fill([
            'mission_id' => $entretien->mission_id,
            'service_id' => $entretien->service_id,
            'entretien_id' => $entretien->id,
            'questionnaire_question_id' => $questionId,
            'source_signature' => $signature,
            'title' => $title,
            'description' => $structuredRisk['description'] ?? null,
            'category' => $structuredRisk['category'] ?? ($question['risk_category'] ?? null),
            'probability' => $structuredRisk['probability'] ?? null,
            'impact' => $structuredRisk['impact'] ?? null,
            'criticality' => $structuredRisk['criticality'] ?? null,
            'recommendation' => $structuredRisk['recommendation'] ?? null,
            'created_by' => $risk->exists ? $risk->created_by : $userId,
            'ai_generated' => false,
            'validated_by_human' => $risk->exists ? (bool) $risk->validated_by_human : false,
            'lifecycle_status' => $risk->exists
                ? ($risk->lifecycle_status ?: RiskLifecycleStatus::Detected->value)
                : RiskLifecycleStatus::Detected->value,
        ]);
        $risk->save();

        $this->audit->riskIdentified($request->user(), $risk->fresh(), $request);
        $this->metrics->increment(
            metricKey: 'core_runtime.questionnaire.identified_risk.captured',
            delta: 1,
            dimensions: ['questionnaire_question_id' => (string) $questionId],
            scopeType: 'mission',
            scopeId: $entretien->mission_id,
        );
        $this->events->record(
            eventName: 'core_runtime.questionnaire.identified_risk_captured',
            payload: [
                'identified_risk_id' => $risk->id,
                'entretien_id' => $entretien->id,
                'questionnaire_question_id' => $questionId,
            ],
            context: [],
            aggregateType: 'identified_risk',
            aggregateId: $risk->id,
            actor: $request->user(),
            missionId: $entretien->mission_id,
            idempotencyKey: 'identified-risk:'.$signature,
        );

        return $risk->fresh();
    }
}
