<?php

namespace App\Module\Production\Factor\Assemblers;

use App\Entity\AgreementLine;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;

interface FactorAssemblerInterface
{
    public function supports(FactorSource $source): bool;
    public function assemble(
        AssembledFactorDTO $previousFactor,
        AgreementLine $agreementLine,
        ?string $departmentSlug,
        array $factorsPool,
    ): AssembledFactorDTO;
}