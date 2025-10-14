<?php

namespace App\Module\Authorization\Service;

use App\Module\Authorization\Entity\AuthAbstractGrantValue;

class GrantValueSupplier
{
    public function getValue(AuthAbstractGrantValue $grantValue): bool
    {
        return $grantValue->getValue() && $grantValue->getGrant()->getModule()->isActive();
    }
}