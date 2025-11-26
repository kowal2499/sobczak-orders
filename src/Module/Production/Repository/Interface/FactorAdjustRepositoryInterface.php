<?php

namespace App\Module\Production\Repository\Interface;

use App\Module\Production\Entity\FactorAdjust;

interface FactorAdjustRepositoryInterface
{
    public function add(FactorAdjust $factorAdjust, bool $flush = true): void;
}