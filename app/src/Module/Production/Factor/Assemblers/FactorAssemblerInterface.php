<?php

namespace App\Module\Production\Factor\Assemblers;

use App\Entity\AgreementLine;
use App\Module\Production\Entity\Factor;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;

interface FactorAssemblerInterface
{
    public function supports(FactorSource $source): bool;

    /**
     * @param float $previousFactor
     * @param AgreementLine $agreementLine
     * @param string|null $departmentSlug
     * @param Factor[] $factorsPool
     * @return AssembledFactorDTO|null
     */
    public function assemble(
        float $previousFactor,
        AgreementLine $agreementLine,
        ?string $departmentSlug,
        array $factorsPool,
    ): ?AssembledFactorDTO;
}
