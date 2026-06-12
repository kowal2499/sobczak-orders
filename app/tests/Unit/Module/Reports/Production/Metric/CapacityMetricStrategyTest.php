<?php

namespace App\Tests\Unit\Module\Reports\Production\Metric;

use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\ReadModel\CustomerRM;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\Reports\Production\Metric\CapacityMetricStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;

class CapacityMetricStrategyTest extends TestCase
{
    public function testKeepsOnlyProductionsEndingInRangeForDefaultDepartments(): void
    {
        // Given
        $ratio = new AssembledFactorDTO(1.0);
        $bonus = new AssembledFactorDTO(2.0);

        $line = $this->makeLine(1, [
            // w zakresie, dział domyślny → rekord
            $this->prod('dpt02', dateEnd: new \DateTime('2026-05-15'), ratio: $ratio, bonus: $bonus),
            // poza zakresem (dateEnd) → pominięte
            $this->prod('dpt03', dateEnd: new \DateTime('2026-06-15'), ratio: $ratio, bonus: $bonus),
            // dział spoza domyślnych → pominięte
            $this->prod('custom', dateEnd: new \DateTime('2026-05-10'), ratio: $ratio, bonus: $bonus),
        ]);

        $strategy = $this->makeStrategy([$line]);

        // When
        $result = $strategy->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));

        // Then
        $this->assertCount(1, $result);
        $this->assertSame('dpt02', $result[0]->getDepartmentSlug());
        // Capacity używa factorRatio (1.0), nie factorBonus (2.0)
        $this->assertSame(1.0, $result[0]->getFactors()->factor);
    }

    public function testGhostHandling(): void
    {
        $line = $this->makeLine(2, [
            $this->prod('dpt02', dateEnd: new \DateTime('2026-05-15'), isGhost: false),
            $this->prod('dpt03', dateEnd: new \DateTime('2026-05-20'), isGhost: true),
        ]);

        // domyślnie ghost pominięty
        $resultNoGhost = $this->makeStrategy([$line])->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'));
        $this->assertCount(1, $resultNoGhost);
        $this->assertFalse($resultNoGhost[0]->getIsGhost());

        // includeGhost=true → oba
        $resultGhost = $this->makeStrategy([$line])->compute(new \DateTime('2026-05-01'), new \DateTime('2026-05-31'), true);
        $this->assertCount(2, $resultGhost);
    }

    private function makeStrategy(array $lines): CapacityMetricStrategy
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

        return new CapacityMetricStrategy($repo, $security);
    }

    private function prod(
        string $slug,
        ?\DateTimeInterface $dateEnd = null,
        ?AssembledFactorDTO $ratio = null,
        ?AssembledFactorDTO $bonus = null,
        bool $isGhost = false,
    ): ProductionRM {
        return new ProductionRM(
            departmentSlug: $slug,
            id: random_int(1, 100000),
            dateStart: new \DateTime('2026-05-01'),
            dateEnd: $dateEnd,
            status: (string) TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
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
