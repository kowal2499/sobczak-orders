<?php

namespace App\Module\Authorization\Doctrine;

use App\Module\Authorization\ValueObject\GrantType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class GrantTypeType extends StringType
{

    public function convertToPHPValue($value, AbstractPlatform $platform): ?GrantType
    {
        return !is_null($value) ? GrantType::tryFrom($value) : null;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value instanceof GrantType ? $value->value : null;
    }

    public function getName(): string
    {
        return 'grant_type';
    }
}