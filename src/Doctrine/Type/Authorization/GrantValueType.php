<?php

namespace App\Doctrine\Type\Authorization;

use App\ValueObject\Authorization\GrantValue;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;

class GrantValueType extends JsonType
{
    public function convertToPHPValue($value, AbstractPlatform $platform): ?GrantValue
    {
        $decodedValue = parent::convertToPHPValue($value, $platform) ?? [];
        if (!$decodedValue) {
            return null;
        }
        return new GrantValue($decodedValue);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (!$value instanceof GrantValue) {
            throw ConversionException::conversionFailedSerialization($value, 'grant_value', 'Invalid argument');
        }

        return parent::convertToDatabaseValue($value->getRawValue(), $platform);

    }

    public function getName(): string
    {
        return 'grant_value';
    }
}