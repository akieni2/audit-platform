<?php

namespace App\Services\Risk;

class RiskMatrixRenderer
{
    /**
     * @param  array<int, array<int, array<string, mixed>>>  $rows
     * @return array<string, mixed>
     */
    public function render(array $rows, string $mode = 'inherent'): array
    {
        $totals = [
            'count' => 0,
            'max_density' => 0,
        ];

        $matrix = collect($rows)->map(function (array $row) use (&$totals, $mode) {
            return collect($row)->map(function (array $cell) use (&$totals, $mode) {
                $count = (int) ($cell['count'] ?? 0);
                $totals['count'] += $count;
                $totals['max_density'] = max($totals['max_density'], $count);

                return [
                    ...$cell,
                    'mode' => $mode,
                    'display_count' => $count,
                    'density' => $count,
                    'color_token' => match (strtolower((string) data_get($cell, 'level.label'))) {
                        'critique' => '#FF5A5A',
                        'élevé', 'eleve', 'high' => '#F97316',
                        'modéré', 'modere', 'medium' => '#F4D000',
                        default => '#00A86B',
                    },
                ];
            })->all();
        })->all();

        return [
            'rows' => $matrix,
            'totals' => $totals,
            'mode' => $mode,
        ];
    }
}
