<?php

namespace App\Tests\Unit\Modules\BonusSettlement;

use App\Entity\User;
use App\Module\BonusSettlement\Entity\BonusPeriod;
use App\Module\BonusSettlement\ValueObject\BonusPeriodStatus;
use PHPUnit\Framework\TestCase;

class BonusPeriodTest extends TestCase
{
    public function testShouldStartOpen(): void
    {
        // When
        $period = new BonusPeriod(2026, 8, 5);

        // Then
        $this->assertSame(BonusPeriodStatus::OPEN, $period->getStatus());
        $this->assertTrue($period->isOpen());
        $this->assertNull($period->getClosedAt());
    }

    public function testShouldCloseAndReopen(): void
    {
        // Given
        $period = new BonusPeriod(2026, 8, 5);
        $user = new User();

        // When
        $period->close($user, new \DateTimeImmutable('2026-09-02 10:00:00'));

        // Then
        $this->assertTrue($period->isClosed());
        $this->assertSame($user, $period->getClosedBy());
        $this->assertNotNull($period->getClosedAt());

        // When
        $period->reopen();

        // Then
        $this->assertTrue($period->isOpen());
        $this->assertNull($period->getClosedBy());
        $this->assertNull($period->getClosedAt());
    }

    public function testShouldSpanWholeMonth(): void
    {
        // Given
        $period = new BonusPeriod(2026, 2, 5);

        // Then
        $this->assertSame('2026-02-01 00:00:00', $period->getRangeStart()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-02-28 23:59:59', $period->getRangeEnd()->format('Y-m-d H:i:s'));
    }

    public function testShouldSpanLeapFebruary(): void
    {
        // Given
        $period = new BonusPeriod(2028, 2, 5);

        // Then
        $this->assertSame('2028-02-29 23:59:59', $period->getRangeEnd()->format('Y-m-d H:i:s'));
    }
}
