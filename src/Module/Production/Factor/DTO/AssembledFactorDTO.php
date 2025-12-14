<?php

namespace App\Module\Production\Factor\DTO;

class AssembledFactorDTO
{
    public float $factor = 0;
    /** @var FactorDTO[] $factorsStack */
    public array $factorsStack = [];
}