<?php

namespace App\Services\Export;

use App\Models\Mission;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TpmoCartographyExportService
{
    private const HEADER_FILL = 'FF0D4F8B';

    private const HEADER_FONT = 'FFFFFFFF';

    private const SUBHEADER_FILL = 'FFE8F0F8';

    public function __construct(
        private CartographyReferenceService $references,
    ) {}

    public function downloadWorkbook(Mission $mission): StreamedResponse
    {
        $spreadsheet = $this->buildWorkbook($mission);
        $safeOrg = preg_replace('/[^\w\-]+/u', '_', $mission->organisation) ?: 'mission';

        return $this->stream($spreadsheet, "Cartographie_TPMO_{$safeOrg}_{$mission->id}.xlsx");
    }

    public function downloadActifs(Mission $mission): StreamedResponse
    {
        $rows = $this->references->cartographyRows($mission);
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);
        $this->buildActifsWorkbook($spreadsheet, $rows, $mission->organisation);

        return $this->stream($spreadsheet, "Identification_Actifs_{$mission->id}.xlsx");
    }

    public function downloadMatrice(Mission $mission): StreamedResponse
    {
        $rows = $this->references->cartographyRows($mission);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('CARTO PAR POSTE');
        $this->fillCartoParPosteSheet($sheet, $rows, $mission->organisation);

        return $this->stream($spreadsheet, "Matrice_Risques_Controles_{$mission->id}.xlsx");
    }

    public function downloadCarteThermique(Mission $mission): StreamedResponse
    {
        $rows = $this->references->cartographyRows($mission);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Carte thermique');
        $this->fillCarteThermiqueSheet($sheet, $rows, $mission->organisation);

        return $this->stream($spreadsheet, "Carte_thermique_{$mission->id}.xlsx");
    }

    public function buildWorkbook(Mission $mission): Spreadsheet
    {
        $rows = $this->references->cartographyRows($mission);
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $this->addSheet($spreadsheet, 'ACTIFS ESSENTIELS', fn (Worksheet $s) => $this->fillActifsSheet(
            $s,
            $rows->where('actif_type', 'ESSENTIEL'),
            'IDENTIFICATIONS DES ACTIFS — ESSENTIELS',
            $mission->organisation,
        ));

        $this->addSheet($spreadsheet, 'ACTIFS SUPPORTS', fn (Worksheet $s) => $this->fillActifsSheet(
            $s,
            $rows->where('actif_type', 'SUPPORT'),
            'IDENTIFICATIONS DES ACTIFS — SUPPORTS',
            $mission->organisation,
        ));

        $this->addSheet($spreadsheet, 'CARTO PAR POSTE', fn (Worksheet $s) => $this->fillCartoParPosteSheet(
            $s,
            $rows,
            $mission->organisation,
        ));

        $this->addSheet($spreadsheet, 'RSQ_INHERENT', fn (Worksheet $s) => $this->fillInherentSheet($s, $rows));
        $this->addSheet($spreadsheet, 'CTRLs', fn (Worksheet $s) => $this->fillControlsSheet($s, $rows));
        $this->addSheet($spreadsheet, 'RSQ_RESIDUEL', fn (Worksheet $s) => $this->fillResidualSheet($s, $rows));
        $this->addSheet($spreadsheet, '4_STRAT', fn (Worksheet $s) => $this->fillStrategySheet($s, $rows));
        $this->addSheet($spreadsheet, 'Carte thermique', fn (Worksheet $s) => $this->fillCarteThermiqueSheet(
            $s,
            $rows,
            $mission->organisation,
        ));

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function buildActifsWorkbook(Spreadsheet $spreadsheet, Collection $rows, string $organisation): void
    {
        $this->addSheet($spreadsheet, 'ACTIFS ESSENTIELS', fn (Worksheet $s) => $this->fillActifsSheet(
            $s,
            $rows->where('actif_type', 'ESSENTIEL'),
            'IDENTIFICATIONS DES ACTIFS — ESSENTIELS',
            $organisation,
        ));
        $this->addSheet($spreadsheet, 'ACTIFS SUPPORTS', fn (Worksheet $s) => $this->fillActifsSheet(
            $s,
            $rows->where('actif_type', 'SUPPORT'),
            'IDENTIFICATIONS DES ACTIFS — SUPPORTS',
            $organisation,
        ));
        $spreadsheet->setActiveSheetIndex(0);
    }

    /**
     * @param  callable(Worksheet): void  $builder
     */
    private function addSheet(Spreadsheet $spreadsheet, string $title, callable $builder): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($this->truncateSheetTitle($title));
        $builder($sheet);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function fillActifsSheet(Worksheet $sheet, Collection $rows, string $title, string $organisation): void
    {
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:I1');
        $this->styleTitleRow($sheet, 'A1:I1');
        $sheet->setCellValue('A2', 'Mission : '.$organisation);

        $headers = [
            'N° ACTIF', 'TYPE D\'ACTIFS', 'NOM ACTIFS', 'PROCESSUS',
            'GESTIONNAIRE (Propriétaire)', 'Objectifs du Process',
            'VULNERABILITES', 'MENACES', 'CONSEQUENCES',
        ];
        $this->writeHeaderRow($sheet, 4, $headers);

        $rowNum = 5;
        $seen = [];
        foreach ($rows as $row) {
            $code = $row['actif_code'];
            if (isset($seen[$code])) {
                continue;
            }
            $seen[$code] = true;

            $sheet->fromArray([
                $row['actif_code'],
                $row['actif_type'],
                $row['actif_nom'],
                $row['processus'],
                $row['gestionnaire'],
                $row['objectifs'],
                $row['vulnerabilites'],
                $row['menaces'],
                $row['consequences'],
            ], null, 'A'.$rowNum);
            $rowNum++;
        }

        $this->autoSizeColumns($sheet, 'A', 'I');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function fillCartoParPosteSheet(Worksheet $sheet, Collection $rows, string $organisation): void
    {
        $sheet->setCellValue('A1', 'CARTOGRAPHIE DES RISQUES ET CONTRÔLES — TPMO');
        $sheet->mergeCells('A1:O1');
        $this->styleTitleRow($sheet, 'A1:O1');
        $sheet->setCellValue('A2', 'Mission : '.$organisation);

        $sheet->setCellValue('F3', 'EVALUATION ET PRIORISATION DES RISQUES');
        $sheet->mergeCells('F3:J3');
        $this->styleSubHeader($sheet, 'F3:J3');

        $headers = [
            'N° ACTIF', 'TYPE D\'ACTIFS', 'Ref. Risque', 'Libellé du risque', 'Catégorie',
            'Probabilité (Fréquence)', 'Impact (Gravité)', 'Risque Inhérent (Criticité)',
            'Vélocité', 'Tendance', 'Mesures de Contrôle (Actuelle)',
            'Adéquation', 'Efficience', 'Prob. résiduelle', 'Impact résiduel',
        ];
        $this->writeHeaderRow($sheet, 4, $headers);

        $rowNum = 5;
        foreach ($rows as $row) {
            if ($row['risk_ref'] === '—') {
                continue;
            }

            $sheet->fromArray([
                $row['actif_code'],
                $row['actif_type'],
                $row['risk_ref'],
                $row['risk_label'],
                $row['categorie'],
                $row['probabilite_inherent'],
                $row['impact_inherent'],
                $row['score_inherent'],
                $row['velocite'],
                $row['tendance'],
                $row['controles'],
                $row['adequation'],
                $row['efficience'],
                $row['probabilite_residuel'],
                $row['impact_residuel'],
            ], null, 'A'.$rowNum);
            $rowNum++;
        }

        $this->autoSizeColumns($sheet, 'A', 'O');
        $sheet->getStyle('K5:K'.max(5, $rowNum - 1))->getAlignment()->setWrapText(true);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function fillInherentSheet(Worksheet $sheet, Collection $rows): void
    {
        $this->writeHeaderRow($sheet, 3, [
            'Ref. Risque', 'Libellé du risque', 'Catégorie',
            'Probabilité', 'Impact', 'Criticité inhérente',
        ]);

        $rowNum = 4;
        foreach ($rows as $row) {
            if ($row['risk_ref'] === '—') {
                continue;
            }
            $sheet->fromArray([
                $row['risk_ref'],
                $row['risk_label'],
                $row['categorie'],
                $row['probabilite_inherent'],
                $row['impact_inherent'],
                $row['score_inherent'],
            ], null, 'A'.$rowNum);
            $rowNum++;
        }
        $this->autoSizeColumns($sheet, 'A', 'F');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function fillControlsSheet(Worksheet $sheet, Collection $rows): void
    {
        $this->writeHeaderRow($sheet, 3, [
            'Ref. Risque', 'Libellé du risque', 'Catégorie',
            'Mesures de Contrôle', 'Adéquation', 'Efficience',
        ]);

        $rowNum = 4;
        foreach ($rows as $row) {
            if ($row['risk_ref'] === '—') {
                continue;
            }
            $sheet->fromArray([
                $row['risk_ref'],
                $row['risk_label'],
                $row['categorie'],
                $row['controles'],
                $row['adequation'],
                $row['efficience'],
            ], null, 'A'.$rowNum);
            $rowNum++;
        }
        $this->autoSizeColumns($sheet, 'A', 'F');
        $sheet->getStyle('D4:D'.max(4, $rowNum - 1))->getAlignment()->setWrapText(true);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function fillResidualSheet(Worksheet $sheet, Collection $rows): void
    {
        $this->writeHeaderRow($sheet, 3, [
            'Ref. Risque', 'Libellé du risque', 'Catégorie',
            'Probabilité résiduelle', 'Impact résiduel', 'Score résiduel',
        ]);

        $rowNum = 4;
        foreach ($rows as $row) {
            if ($row['risk_ref'] === '—') {
                continue;
            }
            $sheet->fromArray([
                $row['risk_ref'],
                $row['risk_label'],
                $row['categorie'],
                $row['probabilite_residuel'],
                $row['impact_residuel'],
                $row['score_residuel'],
            ], null, 'A'.$rowNum);
            $rowNum++;
        }
        $this->autoSizeColumns($sheet, 'A', 'F');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function fillStrategySheet(Worksheet $sheet, Collection $rows): void
    {
        $this->writeHeaderRow($sheet, 3, [
            'Ref. Risque', 'Libellé du risque', 'Traiter', 'Transférer (partager)', 'Tolérer (Accepter)',
        ]);

        $rowNum = 4;
        foreach ($rows as $row) {
            if ($row['risk_ref'] === '—') {
                continue;
            }
            $sheet->fromArray([
                $row['risk_ref'],
                $row['risk_label'],
                $row['strategie_traiter'],
                $row['strategie_transferer'],
                $row['strategie_accepter'],
            ], null, 'A'.$rowNum);
            $rowNum++;
        }
        $this->autoSizeColumns($sheet, 'A', 'E');
        $sheet->getStyle('C4:E'.max(4, $rowNum - 1))->getAlignment()->setWrapText(true);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function fillCarteThermiqueSheet(Worksheet $sheet, Collection $rows, string $organisation): void
    {
        $sheet->setCellValue('A1', 'Cartographie des risques — Carte thermique');
        $sheet->mergeCells('A1:D1');
        $this->styleTitleRow($sheet, 'A1:D1');
        $sheet->setCellValue('A2', 'Mission : '.$organisation);

        $this->writeHeaderRow($sheet, 4, ['#', 'Réf. risque', 'Libellé', 'Impact']);

        $rowNum = 5;
        $index = 0;
        foreach ($rows as $row) {
            if ($row['risk_ref'] === '—') {
                continue;
            }
            $index++;
            $sheet->fromArray([
                $index,
                $row['risk_ref'],
                $row['risk_label'],
                $row['impact_inherent'],
            ], null, 'A'.$rowNum);
            $this->applyHeatTint($sheet, 'D'.$rowNum, (int) $row['impact_inherent']);
            $rowNum++;
        }

        $this->autoSizeColumns($sheet, 'A', 'D');
    }

    private function applyHeatTint(Worksheet $sheet, string $cell, int $impact): void
    {
        $color = match (true) {
            $impact >= 5 => 'FFFF6B6B',
            $impact >= 4 => 'FFFFB347',
            $impact >= 3 => 'FFFFE066',
            $impact >= 2 => 'FFB8E986',
            default => 'FFE8F5E9',
        };

        $sheet->getStyle($cell)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB($color);
    }

    /**
     * @param  list<string>  $headers
     */
    private function writeHeaderRow(Worksheet $sheet, int $row, array $headers): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.$row, $header);
            $col++;
        }

        $lastCol = chr(ord('A') + count($headers) - 1);
        $range = 'A'.$row.':'.$lastCol.$row;
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => self::HEADER_FONT]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::HEADER_FILL],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ]);
    }

    private function styleTitleRow(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => self::HEADER_FONT]],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::HEADER_FILL],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    private function styleSubHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::SUBHEADER_FILL],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    private function autoSizeColumns(Worksheet $sheet, string $from, string $to): void
    {
        for ($col = $from; $col <= $to; $col++) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function truncateSheetTitle(string $title): string
    {
        return mb_substr($title, 0, 31);
    }

    private function stream(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
        ]);
    }
}
