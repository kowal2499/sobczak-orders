<?php

namespace App\Module\Production\Factor\Parts;

use App\Entity\AgreementLine;
use App\Modules\Reports\Production\DTO\FactorDTO;

interface FactorPartInterface
{
    public function getFor(AgreementLine $agreementLine, string $departmentSlug): FactorDTO;
}