<?php

namespace Tests\Unit;

use App\Support\UiLabel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UiLabelTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function labels(): array
    {
        return [
            'low' => ['low', 'Faible'],
            'medium' => ['medium', 'Moyen'],
            'high' => ['high', 'Élevé'],
            'critical' => ['critical', 'Critique'],
            'accountable' => ['accountable', 'Responsable final'],
            'pending' => ['pending', 'En attente'],
            'approved' => ['approved', 'Approuvé'],
            'draft' => ['draft', 'Brouillon'],
        ];
    }

    #[DataProvider('labels')]
    public function test_it_translates_technical_values_for_the_interface(string $value, string $expected): void
    {
        $this->assertSame($expected, UiLabel::translate($value));
    }

    public function test_it_preserves_unknown_business_values(): void
    {
        $this->assertSame('Valeur métier', UiLabel::translate('Valeur métier'));
    }
}
