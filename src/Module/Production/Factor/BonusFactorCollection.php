<?php

namespace App\Module\Production\Factor;

use App\Module\Production\Factor\Parts\AgreementLinePart;
use App\Module\Production\Factor\Parts\DepartmentBonusPart;
use App\Repository\AgreementLineRepository;

class BonusFactorCollection implements FactorCollectionProviderInterface
{

    public function __construct(
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly AgreementLinePart       $agreementPart,
        private readonly DepartmentBonusPart     $departmentBonusPart,
    ) {
    }

    public function getFactors(int $agreementLineId, string $departmentSlug): array
    {
        $agreementLine = $this->agreementLineRepository->find($agreementLineId);
        if (!$agreementLine) {
            throw new \InvalidArgumentException('Agreement line not found');
        }

        return [
            $this->agreementPart->getFor($agreementLine, $departmentSlug),
            $this->departmentBonusPart->getFor($agreementLine, $departmentSlug),
        ];
    }
}