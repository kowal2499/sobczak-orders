<?php

namespace App\Tests\Unit\Module\Reports\Production\Metric;

use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\CustomerRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\Reports\Production\Metric\DepartmentsBonusOnTimeMetricStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;

class DepartmentsBonusOnTimeMetricStrategyTest extends TestCase
{
    public function testMarksOnTimeWhenCompletedInsideWindow(): void
    {
        // ukończenie w oknie [01-05, 10-05]
        $line = $this->makeLine(1, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-01'),
                dateEnd: new \DateTime('2026-05-10'),
                completedAt: new \DateTime('2026-05-05 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $result = $this->makeStrategy([$line])->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->getOnTime());
        $this->assertSame(2.0, $result[0]->getFactors()->factor);
    }

    public function testIncludesButMarksOffTimeWhenCompletedAfterWindow(): void
    {
        // ukończenie po oknie — rekord obecny, ale onTime=false
        $line = $this->makeLine(2, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-01'),
                dateEnd: new \DateTime('2026-05-10'),
                completedAt: new \DateTime('2026-05-20 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $result = $this->makeStrategy([$line])->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));

        $this->assertCount(1, $result);
        $this->assertFalse($result[0]->getOnTime());
    }

    public function testMarksOffTimeWhenCompletedBeforeWindow(): void
    {
        $line = $this->makeLine(3, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-10'),
                dateEnd: new \DateTime('2026-05-20'),
                completedAt: new \DateTime('2026-05-05 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $result = $this->makeStrategy([$line])->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));

        $this->assertCount(1, $result);
        $this->assertFalse($result[0]->getOnTime());
    }

    public function testMarksOffTimeWhenWindowMissing(): void
    {
        $line = $this->makeLine(4, [
            $this->prod(
                'dpt03',
                dateStart: null,
                dateEnd: null,
                completedAt: new \DateTime('2026-05-05 12:00:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $result = $this->makeStrategy([$line])->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));

        $this->assertCount(1, $result);
        $this->assertFalse($result[0]->getOnTime());
    }

    public function testTreatsWindowBoundariesAsOnTime(): void
    {
        // ukończenie ostatniego dnia okna wieczorem — nadal w terminie (koniec = 23:59:59)
        $line = $this->makeLine(5, [
            $this->prod(
                'dpt03',
                dateStart: new \DateTime('2026-05-01'),
                dateEnd: new \DateTime('2026-05-10'),
                completedAt: new \DateTime('2026-05-10 23:30:00'),
                bonus: new AssembledFactorDTO(2.0),
            ),
        ]);

        $result = $this->makeStrategy([$line])->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->getOnTime());
    }

    private function makeStrategy(array $lines): DepartmentsBonusOnTimeMetricStrategy
    {
        $repo = $this->createMock(AgreementLineRMRepository::class);
        $repo->method('search')->willReturn(new class ($lines) {
            public function __construct(private array $lines)
            {
            }

            public function getResult(): array
            {
                return $this->lines;
            }
        });

        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(false);

        return new DepartmentsBonusOnTimeMetricStrategy($repo, $security);
    }

    private function prod(
        string $slug,
        ?\DateTimeInterface $dateStart,
        ?\DateTimeInterface $dateEnd,
        ?\DateTimeInterface $completedAt,
        ?AssembledFactorDTO $bonus = null,
    ): ProductionRM {
        return new ProductionRM(
            departmentSlug: $slug,
            id: random_int(1, 100000),
            dateStart: $dateStart,
            dateEnd: $dateEnd,
            status: (string) TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            isCompleted: true,
            completedAt: $completedAt,
            factorRatio: null,
            factorBonus: $bonus,
            isGhost: false,
        );
    }

    private function makeLine(int $id, array $productions): AgreementLineRM
    {
        $line = new AgreementLineRM($id);
        $line->setOrderNumber('ORD-' . $id);
        $line->setConfirmedDate(new \DateTime('2026-05-01'));
        $line->setCustomer(new CustomerRM($id, 'Klient ' . $id));
        $line->setProductName('Produkt ' . $id);
        $line->setFactor(1.0);
        $line->setProductionStartDate(null);
        $line->setProductionEndDate(null);
        $line->setProductions($productions);

        return $line;
    }
}
