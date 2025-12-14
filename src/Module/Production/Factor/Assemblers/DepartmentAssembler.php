<?php

namespace App\Module\Production\Factor\Assemblers;

use App\Entity\AgreementLine;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;

class DepartmentAssembler implements FactorAssemblerInterface
{
    public function supports(FactorSource $source): bool
    {
        return $source === FactorSource::FACTOR_ADJUSTMENT_RATIO;
    }

    public function assemble(
        AssembledFactorDTO $previousFactor,
        AgreementLine $agreementLine,
        ?string $departmentSlug,
        array $factorsPool
    ): AssembledFactorDTO
    {
        return new AssembledFactorDTO();
    }


}