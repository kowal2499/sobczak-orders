<?php

namespace App\Tests\Unit\Module\Reports\Production\Metric;

use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\CustomerRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\Reports\Production\Metric\DepartmentsBonusMetricStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;

class DepartmentsBonusMetricStrategyTest extends TestCase
{
    public function testKeepsOnlyCompletedProductionsWithCompletedAtInRange(): void
    {
        // Given
        $ratio = new AssembledFactorDTO(1.0);
        $bonus = new AssembledFactorDTO(2.0);

        $line = $this->makeLine(1, [
            // zakończone w zakresie → rekord
            $this->prod('dpt03', isCompleted: true, completedAt: new \DateTime('2026-05-15'), ratio: $ratio, bonus: $bonus),
            // niezakończone → pominięte
            $this->prod('dpt04', isCompleted: false, completedAt: new \DateTime('2026-05-16'), ratio: $ratio, bonus: $bonus),
            // zakończone poza zakresem → pominięte
            $this->prod('dpt05', isCompleted: true, completedAt: new \DateTime('2026-06-15'), ratio: $ratio, bonus: $bonus),
            // dział spoza domyślnych → pominięte
            $this->prod('custom', isCompleted: true, completedAt: new \DateTime('2026-05-10'), ratio: $ratio, bonus: $bonus),
        ]);

        $strategy = $this->makeStrategy([$line]);

        // When
        $result = $strategy->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));

        // Then
        $this->assertCount(1, $result);
        $this->assertSame('dpt03', $result[0]->getDepartmentSlug());
        // Bonus używa factorBonus (2.0), nie factorRatio (1.0)
        $this->assertSame(2.0, $result[0]->getFactors()->factor);
    }

    public function testSkipsGhostCompletedProduction(): void
    {
        $line = $this->makeLine(2, [
            $this->prod('dpt03', isCompleted: true, completedAt: new \DateTime('2026-05-15'), isGhost: true),
        ]);

        $result = $this->makeStrategy([$line])->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));

        $this->assertSame([], $result);
    }

    private function makeStrategy(array $lines): DepartmentsBonusMetricStrategy
    {
        $repo = $this->createMock(AgreementLineRMRepository::class);
        $repo->method('search')->willReturn(new class($lines) {
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

        return new DepartmentsBonusMetricStrategy($repo, $security);
    }

    private function prod(
        string $slug,
        bool $isCompleted = false,
        ?\DateTimeInterface $completedAt = null,
        ?AssembledFactorDTO $ratio = null,
        ?AssembledFactorDTO $bonus = null,
        bool $isGhost = false,
    ): ProductionRM {
        return new ProductionRM(
            departmentSlug: $slug,
            id: random_int(1, 100000),
            dateStart: new \DateTime('2026-05-01'),
            dateEnd: new \DateTime('2026-05-02'),
            status: (string) TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            isCompleted: $isCompleted,
            completedAt: $completedAt,
            factorRatio: $ratio,
            factorBonus: $bonus,
            isGhost: $isGhost,
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
