<?php

namespace App\Services\ServerAudit;

use App\Models\Constat;
use App\Models\IdentifiedRisk;
use App\Models\Mission;
use App\Models\ServerAuditControlDefinition;
use App\Models\ServerAuditResult;
use ZipArchive;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ServerAuditExcelExportService
{
    public function build(Mission $mission): string
    {
        $mission->load(['auditeur', 'constats.creator', 'constats.evidences']);
        $results = ServerAuditResult::query()
            ->whereHas('run', fn ($query) => $query->where('mission_id', $mission->id))
            ->with(['run.asset', 'run.rawDocument', 'identifiedRisk', 'controlDefinition.references'])
            ->orderBy('id')->get();
        $risks = IdentifiedRisk::query()->where('mission_id', $mission->id)->get()->keyBy('constat_id');
        $definitions = ServerAuditControlDefinition::query()->with('references')->where('active', true)->orderBy('code')->get();

        $sheets = [
            ['Synthese', $this->summaryRows($mission, $results)],
            ['Grille_Constats', $this->findingRows($mission, $risks)],
            ['Resultats_Techniques', $this->resultRows($results)],
            ['Referentiels', $this->referenceRows($definitions)],
            ['Enumerations', $this->enumerationRows()],
            ['Power_Query', $this->powerQueryRows()],
        ];

        $directory = storage_path('app/exports');
        File::ensureDirectoryExists($directory);
        $path = $directory.'/audit-export-'.Str::uuid().'.xlsx';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $this->writePackage($zip, $sheets);
        $zip->close();

        return $path;
    }

    private function summaryRows(Mission $mission, $results): array
    {
        return [
            ['Indicateur', 'Valeur'],
            ['Mission', $mission->reference ?: '#'.$mission->id],
            ['Organisation', $mission->organisation],
            ['Objet', $mission->objet],
            ['Période', $mission->periode_audit],
            ['Auditeur principal', $mission->auditeur?->name],
            ['Date export', now()->format('Y-m-d H:i:s')],
            ['Contrôles exécutés', $results->count()],
            ['Contrôles conformes', $results->where('status', 'pass')->count()],
            ['Écarts détectés', $results->where('status', 'fail')->count()],
            ['Erreurs de collecte', $results->where('status', 'error')->count()],
            ['Constats', $mission->constats->count()],
            ['Constats définitifs', $mission->constats->where('status', Constat::STATUS_FINAL)->count()],
        ];
    }

    private function findingRows(Mission $mission, $risks): array
    {
        $rows = [[
            'Constat_ID', 'Date_Detection', 'Domaine_Audit', 'Categorie_Controle', 'Systeme_Cible',
            'OS_SGBD_Cible', 'Titre_Constat', 'Referentiel', 'Risque', 'Preuve_Commande',
            'Criticite', 'Priorite_Echeance', 'Recommandation', 'Statut', 'Auditeur', 'Source_Donnee',
        ]];
        foreach ($mission->constats as $constat) {
            $risk = $risks->get($constat->id);
            $meta = $constat->metadata ?? [];
            $snapshot = $meta['control_snapshot'] ?? [];
            $asset = $meta['hostname'] ?? $constat->service?->nom ?? null;
            $proofs = $constat->evidences->map(fn ($document) => $document->original_name.' [SHA-256: '.($document->checksum_sha256 ?: 'n/a').']')->implode(' ; ');
            $rows[] = [
                $constat->reference, $constat->created_at?->format('Y-m-d'), $mission->auditDomain?->name ?: $mission->mission_type,
                $risk?->category, $asset, data_get($meta, 'asset_platform'), $constat->description,
                $this->citation($snapshot['references'] ?? []), $risk?->description ?: $constat->consequence,
                $proofs, $constat->gravite, data_get($meta, 'priority_deadline'), $constat->recommandation,
                Constat::statusLabels()[$constat->status] ?? $constat->status, $constat->creator?->name,
                data_get($meta, 'source', 'plateforme DGCPT'),
            ];
        }
        return $rows;
    }

    private function resultRows($results): array
    {
        $rows = [[
            'Run_UUID', 'Date_Collecte', 'Actif_UUID', 'Hote', 'Adresse_IP', 'Plateforme', 'Type_Actif',
            'Produit', 'Version_Produit', 'Code_Controle', 'Statut', 'Valeur_Attendue', 'Valeur_Observee',
            'Criticite', 'Risque_ID', 'Preuve_SHA256', 'Preuve_Fichier',
        ]];
        foreach ($results as $result) {
            $asset = $result->run->asset;
            $document = $result->run->rawDocument;
            $rows[] = [
                $result->run->run_uuid, $result->run->completed_at?->format('Y-m-d H:i:s'), $asset->asset_uuid,
                $asset->hostname, $asset->ip_address, $asset->platform, $asset->asset_type,
                $asset->product, $asset->product_version, $result->control_code, $result->status,
                $result->expected, $result->observed, $result->criticality, $result->identified_risk_id,
                $document?->checksum_sha256, $document?->original_name,
            ];
        }
        return $rows;
    }

    private function referenceRows($definitions): array
    {
        $rows = [['Code_Controle', 'Version_Controle', 'Titre', 'Organisme', 'Document', 'Version_Document', 'Chapitre', 'Section', 'Disposition', 'Exigence_Resume', 'Traitement', 'Portee']];
        foreach ($definitions as $definition) {
            foreach ($definition->references as $reference) {
                $rows[] = [
                    $definition->code, $definition->version, $definition->title, $reference->authority,
                    $reference->document_title, $reference->document_version, $reference->chapter,
                    $reference->section, $reference->provision_reference, $reference->requirement_summary,
                    $definition->treatment_guidance, json_encode($definition->applicability, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
            }
        }
        return $rows;
    }

    private function enumerationRows(): array
    {
        return [
            ['Criticite', 'Statut_Controle', 'Statut_Constat', 'Priorite_Echeance'],
            ['Critique', 'fail', 'Brouillon', 'P1 — 30 jours'],
            ['Élevée', 'pass', 'Revue de l’équipe', 'P2 — 90 jours'],
            ['Moyenne', 'error', 'Validé par le chef de mission', 'P3 — 180 jours'],
            ['Faible', 'not_applicable', 'Phase contradictoire', 'P4 — 1 an'],
            ['', '', 'Définitif', ''],
        ];
    }

    private function powerQueryRows(): array
    {
        return [
            ['Étape', 'Instruction'],
            ['Source', 'Charger la table Resultats_Techniques depuis ce classeur.'],
            ['Typage', 'Date_Collecte en Date/Heure ; Adresse_IP et codes en Texte.'],
            ['Déduplication', 'Clé conseillée : Actif_UUID + Code_Controle + Date_Collecte.'],
            ['Jointure', 'Joindre Referentiels sur Code_Controle pour enrichir les résultats.'],
            ['Filtrage', 'Conserver Statut = fail pour analyser les écarts et risques provisoires.'],
            ['Traçabilité', 'Conserver Run_UUID et Preuve_SHA256 dans toute restitution.'],
        ];
    }

    private function citation(array $references): string
    {
        return collect($references)->map(fn ($ref) => implode(' ', array_filter([
            $ref['authority'] ?? null, $ref['document'] ?? null, $ref['version'] ?? null,
            isset($ref['chapter']) ? 'chapitre '.$ref['chapter'] : null,
            isset($ref['section']) ? 'section '.$ref['section'] : null,
            isset($ref['provision']) ? '§'.$ref['provision'] : null,
        ])))->implode(' ; ');
    }

    private function writePackage(ZipArchive $zip, array $sheets): void
    {
        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', $this->workbook($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->styles());
        foreach ($sheets as $index => [$name, $rows]) {
            $zip->addFromString('xl/worksheets/sheet'.($index + 1).'.xml', $this->worksheet($rows));
        }
    }

    private function worksheet(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="18"/><sheetData>';
        foreach ($rows as $rowIndex => $row) {
            $xml .= '<row r="'.($rowIndex + 1).'">';
            foreach (array_values($row) as $columnIndex => $value) {
                $ref = $this->column($columnIndex + 1).($rowIndex + 1);
                if (is_int($value) || is_float($value)) $xml .= '<c r="'.$ref.'"'.($rowIndex === 0 ? ' s="1"' : '').'><v>'.$value.'</v></c>';
                else $xml .= '<c r="'.$ref.'" t="inlineStr"'.($rowIndex === 0 ? ' s="1"' : '').'><is><t xml:space="preserve">'.$this->xml((string) ($value ?? '')).'</t></is></c>';
            }
            $xml .= '</row>';
        }
        $lastColumn = $this->column(max(array_map('count', $rows)));
        $xml .= '</sheetData><autoFilter ref="A1:'.$lastColumn.count($rows).'"/><pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/></worksheet>';
        return $xml;
    }

    private function workbook(array $sheets): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        foreach ($sheets as $index => [$name]) $xml .= '<sheet name="'.$this->xml($name).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        return $xml.'</sheets></workbook>';
    }

    private function workbookRels(int $count): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        for ($i = 1; $i <= $count; $i++) $xml .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        return $xml.'<Relationship Id="rId'.($count + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function contentTypes(int $count): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        for ($i = 1; $i <= $count; $i++) $xml .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        return $xml.'</Types>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF073763"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>';
    }

    private function column(int $number): string
    {
        $letters = '';
        while ($number > 0) { $number--; $letters = chr(65 + ($number % 26)).$letters; $number = intdiv($number, 26); }
        return $letters;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
