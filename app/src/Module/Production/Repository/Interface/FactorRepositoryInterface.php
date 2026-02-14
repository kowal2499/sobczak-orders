<?php

namespace App\Module\Production\Repository\Interface;

use App\Module\Production\Entity\Factor;

interface FactorRepositoryInterface
{
    public function save(Factor $factor, bool $flush = true): void;
    public function delete(Factor $factor, bool $flush = true): void;
}
