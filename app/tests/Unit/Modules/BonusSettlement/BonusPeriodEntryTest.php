<?php

namespace App\Tests\Unit\Modules\BonusSettlement;

use App\Entity\User;
use App\Module\BonusSettlement\Entity\BonusPeriod;
use App\Module\BonusSettlement\Entity\BonusPeriodEntry;
use PHPUnit\Framework\TestCase;

class BonusPeriodEntryTest extends TestCase
{
    public function testShouldFallBackToCalculatedFactorsWithoutAdjustment(): void
    {
        // Given
        $entry = $this->makeEntry(62.0);

        // Then
        $this->assertSame(62.0, $entry->getEffectiveFactors());
        $this->assertFalse($entry->isAdjusted());
    }

    public function testShouldPreferAdjustedFactors(): void
    {
        // Given
        $entry = $this->makeEntry(62.0);

        // When
        $entry->adjust(40.0, 'ucieczka z dyżuru');

        // Then
        $this->assertSame(40.0, $entry->getEffectiveFactors());
        $this->assertTrue($entry->isAdjusted());
    }

    /**
     * Pułapka ??: korekta 0 to pełnoprawne odebranie premii, nie brak korekty.
     */
    public function testShouldTreatZeroAdjustmentAsBindingValue(): void
    {
        // Given
        $entry = $this->makeEntry(62.0);

        // When
        $entry->adjust(0.0, 'nieobecność cały miesiąc');

        // Then
        $this->assertSame(0.0, $entry->getEffectiveFactors());
        $this->assertTrue($entry->isAdjusted());
    }

    public function testShouldStampAdjustmentTime(): void
    {
        // Given
        $entry = $this->makeEntry(62.0);
        $at = new \DateTimeImmutable('2026-09-01 09:14:00');

        // When
        $entry->adjust(40.0, null, $at);

        // Then
        $this->assertEquals($at, $entry->getAdjustedAt());
    }

    public function testShouldClearWholeAdjustment(): void
    {
        // Given
        $entry = $this->makeEntry(62.0);
        $entry->adjust(40.0, 'pomyłka');

        // When
        $entry->clearAdjustment();

        // Then
        $this->assertNull($entry->getFactorsAdjusted());
        $this->assertNull($entry->getNote());
        $this->assertNull($entry->getAdjustedAt());
        $this->assertSame(62.0, $entry->getEffectiveFactors());
    }

    public function testShouldRegisterItselfInPeriod(): void
    {
        // Given
        $period = new BonusPeriod(2026, 8, 5);

        // When
        $entry = new BonusPeriodEntry($period, new User(), 'Jan Kowalski', 'dpt04', 'Lakierowanie', 84.0);

        // Then
        $this->assertTrue($period->getEntries()->contains($entry));
    }

    private function makeEntry(float $calculated): BonusPeriodEntry
    {
        return new BonusPeriodEntry(
            new BonusPeriod(2026, 8, 5),
            new User(),
            'Anna Nowak',
            'dpt03',
            'Szlifowanie',
            $calculated
        );
    }
}
