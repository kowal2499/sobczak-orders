<?php

namespace App\Module\Production\Factor\DTO;

class AssembledFactorDTO
{
    public float $factor;
    /** @var FactorDTO[] $factorsStack */
    public array $factorsStack;

    /**
     * @param float $factor
     * @param FactorDTO[] $factorsStack
     */
    public function __construct(float $factor = 0, array $factorsStack = [])
    {
        $this->factor = $factor;
        $this->factorsStack = $factorsStack;
    }

    public function toArray():array
    {
        return [
            'factor' => $this->factor,
            'factorsStack' => array_map(function(FactorDTO $factorDTO) {
                return [
                    'source' => $factorDTO->source->value,
                    'value' => $factorDTO->value,
                    'agreementLineId' => $factorDTO->agreementLineId,
                    'departmentSlug' => $factorDTO->departmentSlug,
                    'description' => $factorDTO->description,
                ];
            }, $this->factorsStack)
        ];
    }

    public static function fromArray(array $data): self
    {
        $factorsStack = array_map(function(array $factorData) {
            return FactorDTO::fromArray($factorData);
        }, $data['factorsStack'] ?? []);

        return new self(
            $data['factor'] ?? 0,
            $factorsStack
        );
    }

}