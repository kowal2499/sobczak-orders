<?php

namespace App\Module\Production\Factor\Assemblers;

use App\Entity\AgreementLine;
use App\Module\Production\Entity\Factor;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\Production\Factor\DTO\FactorDTO;

class DepartmentBonusAssembler implements FactorAssemblerInterface
{
    public function supports(FactorSource $source): bool
    {
        return $source === FactorSource::FACTOR_ADJUSTMENT_BONUS;
    }

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
        array $factorsPool
    ): ?AssembledFactorDTO
    {
        if (count($factorsPool) === 0) {
            return null;
        }

        $result = new AssembledFactorDTO($previousFactor);

        foreach ($factorsPool as $factor) {
            if ($factor->getSource() !== FactorSource::FACTOR_ADJUSTMENT_BONUS) {
                continue;
            }
            if ($departmentSlug && $factor->getDepartmentSlug() !== $departmentSlug) {
                continue;
            }
            $result->factorsStack[] = new FactorDTO(
                FactorSource::FACTOR_ADJUSTMENT_BONUS,
                $factor->getFactorValue(),
                $factor->getId(),
                $departmentSlug,
                $factor->getDescription(),
            );
            $result->factor = $result->factor + $factor->getFactorValue();


        }

        return $result;
    }
}