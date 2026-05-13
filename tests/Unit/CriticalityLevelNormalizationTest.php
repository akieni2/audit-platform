<?php

namespace Tests\Unit;

use App\Domain\Risk\Enums\CriticalityLevel;
use PHPUnit\Framework\TestCase;

class CriticalityLevelNormalizationTest extends TestCase
{
    public function test_from_mixed_normalizes_common_labels(): void
    {
        $this->assertSame(CriticalityLevel::Critical, CriticalityLevel::fromMixed('Critique'));
        $this->assertSame(CriticalityLevel::High, CriticalityLevel::fromMixed('Élevée'));
        $this->assertSame(CriticalityLevel::High, CriticalityLevel::fromMixed('High'));
        $this->assertSame(CriticalityLevel::Medium, CriticalityLevel::fromMixed('Moyenne'));
        $this->assertSame(CriticalityLevel::Low, CriticalityLevel::fromMixed('Low'));
        $this->assertNull(CriticalityLevel::fromMixed(''));
    }
}
