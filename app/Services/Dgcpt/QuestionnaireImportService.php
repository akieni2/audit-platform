<?php

namespace App\Services\Dgcpt;

use App\Models\Dgcpt\AuditDomain;
use App\Models\Dgcpt\AuditTemplate;
use App\Models\Dgcpt\TreasuryEntity;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireSection;
use App\Models\QuestionnaireTemplate;
use App\Services\Questionnaires\WordQuestionnaireExtractionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class QuestionnaireImportService
{
    public function __construct(
        private WordQuestionnaireExtractionService $wordExtractor,
    ) {}

    /** @return array<string, mixed> */
    public function detectContextFromFilename(string $filename): array
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $normalized = Str::of($base)->replace(['_', '-'], ' ')->lower()->squish()->toString();
        $entity = $this->matchEntity($normalized);
        $template = AuditTemplate::query()->where('code', 'TPL_AUDIT_SI_TP')->first();
        $domain = $template?->auditDomain ?? AuditDomain::query()->where('code', 'AUDIT_SI')->first();

        return [
            'filename' => $filename,
            'suggested_entity' => $entity,
            'suggested_domain' => $domain ? ['id' => $domain->id, 'code' => $domain->code, 'name' => $domain->name] : null,
            'suggested_template' => $template ? ['id' => $template->id, 'code' => $template->code, 'name' => $template->name] : null,
            'notes' => 'Détection heuristique — valider avant publication du questionnaire.',
        ];
    }

    /** @param array<string, mixed> $options */
    public function importToQuestionnaireTemplate(UploadedFile $file, array $options = []): QuestionnaireTemplate
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'docx') {
            throw new InvalidArgumentException('Seul l’import structuré DOCX est actuellement pris en charge.');
        }

        $hash = hash_file('sha256', $file->getRealPath());
        if (QuestionnaireTemplate::query()->where('source_document_sha256', $hash)->exists()) {
            throw new InvalidArgumentException('Ce questionnaire a déjà été importé dans la bibliothèque.');
        }

        $extracted = $this->wordExtractor->extract($file->getRealPath());
        if (($extracted['questions'] ?? []) === []) {
            throw new InvalidArgumentException('Aucune question structurée n’a été trouvée dans ce document Word.');
        }

        $detection = $this->detectContextFromFilename($file->getClientOriginalName());
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $name = filled($options['name'] ?? null)
            ? trim((string) $options['name'])
            : Str::of($baseName)->replace(['_', '-'], ' ')->squish()->toString();

        $template = DB::transaction(function () use ($file, $options, $detection, $extracted, $name, $hash): QuestionnaireTemplate {
            $template = QuestionnaireTemplate::query()->create([
                'name' => $name,
                'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
                'mission_type' => $options['mission_type'] ?? 'audit_si',
                'lifecycle_status' => QuestionnaireTemplate::STATUS_DRAFT,
                'active' => false,
                'version' => 1,
                'created_by' => $options['created_by'] ?? null,
                'updated_by' => $options['created_by'] ?? null,
                'source_document_name' => $file->getClientOriginalName(),
                'source_document_sha256' => $hash,
                'governance_tags' => array_values(array_filter([
                    'dgcpt_import', 'questionnaire_word',
                    $detection['suggested_entity']['code'] ?? null,
                    $detection['suggested_domain']['code'] ?? null,
                ])),
                'description' => 'Questionnaire importé depuis '.$file->getClientOriginalName().'. Structure et contenu à valider avant publication.',
            ]);

            $sections = [];
            $positions = [];
            foreach ($extracted['questions'] as $questionData) {
                $themeKey = 'theme|'.$questionData['theme'];
                $theme = $sections[$themeKey] ??= $this->createSection($template, $questionData['theme'], QuestionnaireSection::TYPE_THEME, null, $positions);
                $thematicKey = $themeKey.'|thematic|'.$questionData['thematic'];
                $thematic = $sections[$thematicKey] ??= $this->createSection($template, $questionData['thematic'], QuestionnaireSection::TYPE_THEMATIC, $theme->id, $positions);
                $subthemeKey = $thematicKey.'|subtheme|'.$questionData['subtheme'];
                $subtheme = $sections[$subthemeKey] ??= $this->createSection($template, $questionData['subtheme'], QuestionnaireSection::TYPE_SUBTHEME, $thematic->id, $positions);

                QuestionnaireQuestion::query()->create([
                    'questionnaire_section_id' => $subtheme->id,
                    'code' => 'Q-'.str_pad((string) $questionData['sequence'], 3, '0', STR_PAD_LEFT),
                    'question' => $questionData['question'],
                    'question_type' => QuestionnaireQuestion::TYPE_BOOLEAN_NA,
                    'required' => false,
                    'allows_observation' => true,
                    'allows_risk_detection' => true,
                    'expected_documents' => $questionData['expected_documents'] ?: null,
                    'sort_order' => $subtheme->questions()->count(),
                    'active' => true,
                    'metadata' => ['stakeholders' => $questionData['stakeholders'] ?: null, 'imported_from_word' => true],
                ]);
            }

            return $template;
        });

        $storedPath = $file->storeAs(
            'questionnaire-library/'.$template->id,
            Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.docx',
            'local',
        );
        $template->update(['source_document_path' => $storedPath]);

        return $template->fresh(['sections.questions']);
    }

    /** @param array<string, int> $positions */
    private function createSection(QuestionnaireTemplate $template, string $title, string $type, ?int $parentId, array &$positions): QuestionnaireSection
    {
        $positionKey = $parentId === null ? 'root' : (string) $parentId;
        $position = $positions[$positionKey] ?? 0;
        $positions[$positionKey] = $position + 1;

        return QuestionnaireSection::query()->create([
            'questionnaire_template_id' => $template->id,
            'title' => $title,
            'section_type' => $type,
            'parent_section_id' => $parentId,
            'sort_order' => $position,
        ]);
    }

    /** @return array<string, mixed>|null */
    private function matchEntity(string $normalized): ?array
    {
        $aliases = [
            'lambarene' => 'TP-MO', 'lambaréné' => 'TP-MO', 'port gentil' => 'TP-OM',
            'oyem' => 'TP-WN', 'libreville' => 'TP-EST', 'franceville' => 'TP-HO',
        ];
        foreach ($aliases as $needle => $code) {
            if (! str_contains($normalized, $needle)) {
                continue;
            }
            $entity = TreasuryEntity::query()->where('code', $code)->first();
            if ($entity) {
                return [
                    'id' => $entity->id, 'code' => $entity->code, 'name' => $entity->name,
                    'entity_type' => $entity->entity_type, 'province' => $entity->province,
                ];
            }
        }

        return null;
    }
}
