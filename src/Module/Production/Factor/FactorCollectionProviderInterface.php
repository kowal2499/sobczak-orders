<?php

namespace App\Module\Production\Factor;

interface FactorCollectionProviderInterface
{
    public function getFactors(int $agreementLineId, string $departmentSlug): array;
}