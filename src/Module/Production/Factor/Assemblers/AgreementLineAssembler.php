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

    public function assemble(
        AssembledFactorDTO $previousFactor,
        AgreementLine $agreementLine,
        ?string $departmentSlug,
        array $factorsPool
    ): AssembledFactorDTO
    {
        $factorDTO = $this->resolveFromPool($factorsPool)
            ?? $this->resolveFromAgreement($agreementLine);

        return $factorDTO;
    }

    private function resolveFromAgreement(AgreementLine $agreementLine): ?AssembledFactorDTO
    {
        $stackItem = new FactorDTO();
        $stackItem->agreementLineId = $agreementLine->getId();
        $stackItem->departmentSlug = null;
        $stackItem->description = null;
        $stackItem->value = $agreementLine->getFactor();
        $stackItem->source = FactorSource::AGREEMENT_LINE;
        $assembledFactor = new AssembledFactorDTO();
        $assembledFactor->factor = $stackItem->value;
        $assembledFactor->factorsStack[] = $stackItem;
        return $assembledFactor;
    }

    /**
     * @param Factor[] $pool
     * @return FactorDTO|null
     */
    private function resolveFromPool(array $pool): ?AssembledFactorDTO
    {
        $result = null;
        foreach ($pool as $item) {
            if ($item->getSource() === FactorSource::AGREEMENT_LINE) {
                $stackItem = new FactorDTO();
                $stackItem->agreementLineId = $item->getAgreementLine()->getId();
                $stackItem->departmentSlug = null;
                $stackItem->description = null;
                $stackItem->value = $item->getFactorValue();
                $stackItem->source = $item->getSource();

                $assembledFactor = new AssembledFactorDTO();
                $assembledFactor->factor = $stackItem->value;
                $assembledFactor->factorsStack[] = $stackItem;
                $result = $assembledFactor;
            }
        }
        return $result;
    }
}