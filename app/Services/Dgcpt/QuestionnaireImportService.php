<?php

namespace App\Services\Dgcpt;

use App\Models\Dgcpt\AuditDomain;
use App\Models\Dgcpt\AuditTemplate;
use App\Models\Dgcpt\TreasuryEntity;
use App\Models\Dgcpt\TreasuryService;
use App\Models\QuestionnaireTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Pont d'import DOCX/XLSX → QuestionnaireTemplate existant (runtime inchangé).
 * Phase 6 : parsing documentaire à brancher (PhpWord / lecteur XLSX).
 */
final class QuestionnaireImportService
{
    public function __construct(
        private DgcptHierarchyService $hierarchy,
    ) {}

    /**
     * @return array<string, mixed>
     */
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
            'suggested_domain' => $domain ? [
                'id' => $domain->id,
                'code' => $domain->code,
                'name' => $domain->name,
            ] : null,
            'suggested_template' => $template ? [
                'id' => $template->id,
                'code' => $template->code,
                'name' => $template->name,
            ] : null,
            'notes' => 'Détection heuristique — valider avant publication du questionnaire.',
        ];
    }

    /**
     * Crée un QuestionnaireTemplate brouillon à partir d'un import (structure minimale).
     *
     * @param  array<string, mixed>  $options
     */
    public function importToQuestionnaireTemplate(UploadedFile $file, array $options = []): QuestionnaireTemplate
    {
        $detection = $this->detectContextFromFilename($file->getClientOriginalName());
        $name = $options['name'] ?? ('Import — '.$file->getClientOriginalName());

        return QuestionnaireTemplate::query()->create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'mission_type' => $options['mission_type'] ?? 'audit',
            'lifecycle_status' => QuestionnaireTemplate::STATUS_DRAFT,
            'active' => false,
            'version' => 1,
            'created_by' => $options['created_by'] ?? null,
            'updated_by' => $options['created_by'] ?? null,
            'governance_tags' => array_values(array_filter([
                'dgcpt_import',
                $detection['suggested_entity']['code'] ?? null,
                $detection['suggested_domain']['code'] ?? null,
                'source:'.pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            ])),
            'description' => trim(
                'Import DGCPT — '.$file->getClientOriginalName()
                ."\n".($detection['suggested_entity']['name'] ?? 'Entité à préciser')
            ),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function matchEntity(string $normalized): ?array
    {
        $aliases = [
            'lambarene' => 'TP-MO',
            'lambaréné' => 'TP-MO',
            'port gentil' => 'TP-OM',
            'oyem' => 'TP-WN',
            'libreville' => 'TP-EST',
            'franceville' => 'TP-HO',
        ];

        foreach ($aliases as $needle => $code) {
            if (str_contains($normalized, $needle)) {
                $entity = TreasuryEntity::query()->where('code', $code)->first();
                if ($entity) {
                    return [
                        'id' => $entity->id,
                        'code' => $entity->code,
                        'name' => $entity->name,
                        'entity_type' => $entity->entity_type,
                        'province' => $entity->province,
                    ];
                }
            }
        }

        if (str_starts_with($normalized, 'tp ')) {
            $entity = TreasuryEntity::query()
                ->where('entity_type', 'provincial')
                ->active()
                ->first();

            return $entity ? [
                'id' => $entity->id,
                'code' => $entity->code,
                'name' => $entity->name,
                'entity_type' => $entity->entity_type,
                'province' => $entity->province,
            ] : null;
        }

        return null;
    }
}
