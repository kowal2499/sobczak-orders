<?php

namespace App\Module\Production\Factor\Parts;

use App\Entity\AgreementLine;
use App\Module\Production\Entity\FactorSource;
use App\Modules\Reports\Production\DTO\FactorDTO;

class AgreementLinePart implements FactorPartInterface
{
    public function getFor(AgreementLine $agreementLine, string $departmentSlug): FactorDTO
    {
        return new FactorDTO(
            FactorSource::AGREEMENT_LINE,
            $agreementLine->getId(),
            $agreementLine->getFactor(),
        );
    }
}