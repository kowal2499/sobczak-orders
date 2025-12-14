<?php

namespace App\Module\Production\Factor;

use App\Entity\AgreementLine;
use App\Module\Production\Entity\Factor;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\Assemblers\AgreementLineAssembler;
use App\Module\Production\Factor\Assemblers\DepartmentAssembler;
use App\Module\Production\Factor\Assemblers\DepartmentBonusAssembler;
use App\Module\Production\Factor\Assemblers\FactorAssemblerInterface;
use App\Module\Production\Factor\DTO\AssembledFactorDTO;

class FactorCalculator
{
    private const RECIPES = [
        'agreement_line' => [
            FactorSource::AGREEMENT_LINE
        ],
        'factor_adjustment_bonus' => [
            FactorSource::AGREEMENT_LINE,
            FactorSource::FACTOR_ADJUSTMENT_RATIO,
            FactorSource::FACTOR_ADJUSTMENT_BONUS,
        ],
        'factor_adjustment_ratio' => [
            FactorSource::AGREEMENT_LINE,
            FactorSource::FACTOR_ADJUSTMENT_RATIO
        ],
    ];

    /** @var $assemblers FactorAssemblerInterface[] */
    private array $assemblers;

    public function __construct()
    {
        $this->assemblers = [
            new AgreementLineAssembler(),
            new DepartmentAssembler(),
            new DepartmentBonusAssembler(),
        ];
    }

    /**
     * @param AgreementLine $agreementLine
     * @param string|null $departmentSlug
     * @param array<Factor> $factorsPool
     * @param FactorSource $targetSource
     * @return AssembledFactorDTO
     */
    public function calculate(
        AgreementLine $agreementLine,
        ?string $departmentSlug,
        array $factorsPool,
        FactorSource $targetSource,
    ): AssembledFactorDTO
    {
        $recipe = self::RECIPES[$targetSource->value] ?? null;
        if ($recipe === null) {
            throw new \InvalidArgumentException("No recipe found for target source: " . $targetSource->value);
        }

        $result = new AssembledFactorDTO();

        foreach ($recipe as $step) {
            foreach ($this->assemblers as $assembler) {
                if ($assembler->supports($step)) {

                    $assembledFactor = $assembler->assemble(
                        $result->factor,
                        $agreementLine,
                        $departmentSlug,
                        $factorsPool,
                    );

                    if (!$assembledFactor) {
                        continue;
                    }

                    $result->factor = $assembledFactor->factor;
                    $result->factorsStack = array_merge(
                        $result->factorsStack,
                        $assembledFactor->factorsStack
                    );

                }
            }
        }

        return $result;
    }
}