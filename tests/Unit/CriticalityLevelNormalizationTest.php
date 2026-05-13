<?php

namespace Tests\Unit;

use App\Domain\Risk\Enums\CriticalityLevel;
use PHPUnit\Framework\TestCase;

class CriticalityLevelNormalizationTest extends TestCase
{
    public function test_from_mixed_normalizes_common_labels(): void
    {
        $this->assertSame(CriticalityLevel::Critique, CriticalityLevel::fromMixed('Critique'));
        $this->assertSame(CriticalityLevel::Eleve, CriticalityLevel::fromMixed('Élevée'));
        $this->assertSame(CriticalityLevel::Eleve, CriticalityLevel::fromMixed('High'));
        $this->assertSame(CriticalityLevel::Moyen, CriticalityLevel::fromMixed('Moyenne'));
        $this->assertSame(CriticalityLevel::Faible, CriticalityLevel::fromMixed('Low'));
        $this->assertNull(CriticalityLevel::fromMixed(''));
    }
}
