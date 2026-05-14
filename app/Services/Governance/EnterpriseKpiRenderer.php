<?php

namespace App\Services\Governance;

class EnterpriseKpiRenderer
{
    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<int, array<string, mixed>>
     */
    public function render(array $snapshot): array
    {
        $cards = [];

        foreach ((array) ($snapshot['executive_kpis'] ?? []) as $label => $value) {
            $cards[] = [
                'label' => \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $label)),
                'value' => $value,
                'accent' => '#73D8FF',
            ];
        }

        foreach ((array) ($snapshot['governance'] ?? []) as $label => $value) {
            $cards[] = [
                'label' => \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $label)),
                'value' => $value,
                'accent' => '#00D1FF',
            ];
        }

        return $cards;
    }
}
