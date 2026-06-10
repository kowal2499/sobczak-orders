<?php

namespace App\Tests\Unit\Module\Reports\Production;

use App\Entity\AgreementLine;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\Production\Factor\FactorCalculator;
use App\Module\Reports\Production\DTO\ProductionReportRecordDTO;
use App\Module\Reports\Production\RecordSuppliers\BaseSupplier;
use App\Repository\AgreementLineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

/**
 * Testy jednostkowe transformacji surowych wierszy → ProductionReportRecordDTO[].
 * Repozytorium i kalkulator współczynników są mockowane (logika mapowania jest czysta).
 */
class BaseSupplierTest extends TestCase
{
    public function testMapsRowFieldsToDto(): void
    {
        // Given
        $start = new \DateTime('2026-05-10');
        $end = new \DateTime('2026-05-15');
        $completedAt = new \DateTime('2026-05-15 12:00');
        $confirmedDate = new \DateTime('2026-05-01');

        $rows = [[
            'id' => 1,
            'departmentSlug' => 'dpt02',
            'dateStart' => $start,
            'dateEnd' => $end,
            'status' => '3',
            'completedAt' => $completedAt,
            'factor' => 2.5,
            'productName' => 'Schody A',
            'productionStartDate' => $start,
            'productionCompletionDate' => $completedAt,
            'orderNumber' => 'ORD-1',
            'confirmedDate' => $confirmedDate,
            'customerName' => 'Klient X',
            'isGhost' => false,
        ]];

        $assembled = new AssembledFactorDTO(2.5, []);
        $supplier = $this->makeSupplier($rows[0]['id'], $assembled);

        // When
        $result = $this->invokeTransform($supplier, $rows, FactorSource::FACTOR_ADJUSTMENT_BONUS);

        // Then
        $this->assertCount(1, $result);
        $dto = $result[0];
        $this->assertInstanceOf(ProductionReportRecordDTO::class, $dto);
        $this->assertSame('dpt02', $dto->getDepartmentSlug());
        $this->assertSame($start, $dto->getDateStart());
        $this->assertSame($end, $dto->getDateEnd());
        $this->assertSame('3', $dto->getStatus());
        $this->assertSame($completedAt, $dto->getCompletedAt());
        $this->assertFalse($dto->getIsGhost());
        $this->assertSame($assembled, $dto->getFactors());

        $this->assertSame(1, $dto->getAgreementLine()->getId());
        $this->assertSame(2.5, $dto->getAgreementLine()->getFactor());
        $this->assertSame('Schody A', $dto->getAgreementLine()->getProductName());
        $this->assertSame('ORD-1', $dto->getAgreement()->getOrderNumber());
        $this->assertSame('Klient X', $dto->getCustomer()->getName());
    }

    public function testFactorsAreNullWhenAgreementLineNotFound(): void
    {
        // Given — repozytorium nie zwraca encji dla id wiersza
        $rows = [['id' => 99, 'departmentSlug' => 'dpt01', 'isGhost' => true]];
        $supplier = $this->makeSupplier(matchedId: null, assembled: null);

        // When
        $result = $this->invokeTransform($supplier, $rows, FactorSource::FACTOR_ADJUSTMENT_RATIO);

        // Then
        $this->assertNull($result[0]->getFactors());
        $this->assertTrue($result[0]->getIsGhost());
        $this->assertSame('dpt01', $result[0]->getDepartmentSlug());
    }

    public function testPassesTargetFactorSourceToCalculator(): void
    {
        // Given
        $rows = [['id' => 5, 'departmentSlug' => 'dpt03']];

        $agreementLine = $this->createMock(AgreementLine::class);
        $agreementLine->method('getId')->willReturn(5);
        $agreementLine->method('getFactors')->willReturn(new ArrayCollection());

        $repository = $this->createMock(AgreementLineRepository::class);
        $repository->method('findWithFactors')->willReturn([$agreementLine]);

        $calculator = $this->createMock(FactorCalculator::class);
        $calculator->expects($this->once())
            ->method('calculate')
            ->with($agreementLine, 'dpt03', [], FactorSource::FACTOR_ADJUSTMENT_BONUS)
            ->willReturn(new AssembledFactorDTO());

        $supplier = new BaseSupplier($repository, $calculator);

        // When
        $this->invokeTransform($supplier, $rows, FactorSource::FACTOR_ADJUSTMENT_BONUS);
    }

    private function makeSupplier(?int $matchedId, ?AssembledFactorDTO $assembled): BaseSupplier
    {
        $repository = $this->createMock(AgreementLineRepository::class);
        $calculator = $this->createMock(FactorCalculator::class);

        if ($matchedId !== null) {
            $agreementLine = $this->createMock(AgreementLine::class);
            $agreementLine->method('getId')->willReturn($matchedId);
            $agreementLine->method('getFactors')->willReturn(new ArrayCollection());
            $repository->method('findWithFactors')->willReturn([$agreementLine]);
            $calculator->method('calculate')->willReturn($assembled ?? new AssembledFactorDTO());
        } else {
            $repository->method('findWithFactors')->willReturn([]);
        }

        return new BaseSupplier($repository, $calculator);
    }

    private function invokeTransform(BaseSupplier $supplier, array $rows, FactorSource $target): array
    {
        $method = new \ReflectionMethod(BaseSupplier::class, 'transformRows');
        $method->setAccessible(true);
        return $method->invoke($supplier, $rows, $target);
    }
}
