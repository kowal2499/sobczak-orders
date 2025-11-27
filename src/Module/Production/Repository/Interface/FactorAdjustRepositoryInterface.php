<?php

namespace App\Module\Production\Repository\Interface;

use App\Module\Production\Entity\FactorAdjust;

interface FactorAdjustRepositoryInterface
{
    public function save(FactorAdjust $factorAdjust, bool $flush = true): void;
    public function delete(FactorAdjust $factorAdjust, bool $flush = true): void;
}