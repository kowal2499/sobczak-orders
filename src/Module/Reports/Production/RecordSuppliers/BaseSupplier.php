<?php

namespace App\Modules\Reports\Production\RecordSuppliers;

use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\Production\Factor\FactorCalculator;
use App\Modules\Reports\Production\DTO\AgreementDTO;
use App\Modules\Reports\Production\DTO\AgreementLineDTO;
use App\Modules\Reports\Production\DTO\CustomerDTO;
use App\Modules\Reports\Production\DTO\ProductionReportRecordDTO;
use App\Repository\AgreementLineRepository;

class BaseSupplier
{
    public function __construct(
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly FactorCalculator $factorCalculator,
    ) {
    }

    /**
     * @param array $rows
     * @param FactorSource $target
     * @return ProductionReportRecordDTO[]
     */
    protected function transformRows(array $rows, FactorSource $target): array
    {
        $agreementLineIds = [];
        $result = [];

        foreach ($rows as $row) {
            if (!in_array($row['id'], array_keys($agreementLineIds))) {
                $agreementLineIds[$row['id']] = null;
            }
        }

        if (!empty(array_keys($agreementLineIds))) {
            foreach ($this->agreementLineRepository->findWithFactors(array_keys($agreementLineIds)) as $agreementLine) {
                $agreementLineIds[$agreementLine->getId()] = $agreementLine;
            }
        }

        foreach ($rows as $row) {
            $factors = null;
            if (isset($agreementLineIds[$row['id']])) {
                $agreementLine = $agreementLineIds[$row['id']];

                $factors = $this->factorCalculator->calculate(
                    $agreementLine,
                    $row['departmentSlug'] ?? null,
                    $agreementLine->getFactors()->toArray(),
                    $target
                );
            }
            $result[] = $this->mapToDto($row, $factors);
        }
        return $result;
    }

    private function mapToDto(array $row, ?AssembledFactorDTO $factors): ProductionReportRecordDTO
    {
        $agreementLine = new AgreementLineDTO(
            $row['id'] ?? null,
            $row['factor'] ?? null,
            $row['productName'] ?? null,
            $row['productionStartDate'] ?? null,
            $row['productionCompletionDate'] ?? null
        );

        $agreement = new AgreementDTO(
            $row['orderNumber'] ?? null,
            $row['confirmedDate'] ?? null
        );

        $customer = new CustomerDTO(
            $row['customerName'] ?? null
        );

        return new ProductionReportRecordDTO(
            $row['departmentSlug'] ?? '',
            $row['completedAt'] ?? null,
            $agreementLine,
            $agreement,
            $customer,
            $factors,
        );
    }
}