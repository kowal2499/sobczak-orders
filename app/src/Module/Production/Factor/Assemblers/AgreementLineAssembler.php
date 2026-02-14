<?php

namespace App\Module\Production\Factor\Assemblers;

use App\Entity\AgreementLine;
use App\Module\Production\Entity\Factor;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use App\Module\Production\Factor\DTO\FactorDTO;

class AgreementLineAssembler implements FactorAssemblerInterface
{
    public function supports(FactorSource $source): bool
    {
        return $source === FactorSource::AGREEMENT_LINE;
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
    ): ?AssembledFactorDTO {
        $poolResult = $this->resolveFromPool($factorsPool);
        if ($poolResult !== null) {
            return $poolResult;
        }
        return $this->resolveFromAgreement($agreementLine);
    }

    private function resolveFromAgreement(AgreementLine $agreementLine): ?AssembledFactorDTO
    {
        return new AssembledFactorDTO(
            $agreementLine->getFactor(),
            [
                new FactorDTO(
                    FactorSource::AGREEMENT_LINE,
                    $agreementLine->getFactor(),
                    $agreementLine->getId(),
                    null,
                    null,
                )
            ]
        );
    }

    /**
     * @param Factor[] $pool
     * @return AssembledFactorDTO|null
     */
    private function resolveFromPool(array $pool): ?AssembledFactorDTO
    {
        $result = null;
        foreach ($pool as $item) {
            if ($item->getSource() === FactorSource::AGREEMENT_LINE) {
                $result = new AssembledFactorDTO(
                    $item->getFactorValue(),
                    [
                        new FactorDTO(
                            FactorSource::AGREEMENT_LINE,
                            $item->getFactorValue(),
                            $item->getAgreementLine()->getId(),
                            null,
                            null,
                        )
                    ]
                );
            }
        }
        return $result;
    }
}
