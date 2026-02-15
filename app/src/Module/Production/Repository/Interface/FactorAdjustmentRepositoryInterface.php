<?php

namespace App\Module\Production\Repository\Interface;

use App\Module\Production\Entity\FactorAdjustment;

interface FactorAdjustmentRepositoryInterface
{
    public function save(FactorAdjustment $factorAdjust, bool $flush = true): void;
    public function delete(FactorAdjustment $factorAdjust, bool $flush = true): void;
}
