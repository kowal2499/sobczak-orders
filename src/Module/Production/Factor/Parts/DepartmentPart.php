<?php

namespace App\Module\Production\Factor\Parts;

use App\Entity\AgreementLine;
use App\Module\Production\Entity\FactorSource;
use App\Modules\Reports\Production\DTO\FactorDTO;

class DepartmentPart implements FactorPartInterface
{
    public function getFor(AgreementLine $agreementLine, string $departmentSlug): FactorDTO
    {
        return new FactorDTO(
            FactorSource::FACTOR_ADJUSTMENT_RATIO,
            $agreementLine->getId(), // should be real factorId
            0, // should be real ratio
        );
    }
}