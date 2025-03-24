<?php

namespace App\Doctrine\Type\Authorization;

use App\ValueObject\Authorization\GrantOption;
use App\ValueObject\Authorization\GrantOptionsCollection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\JsonType;

class GrantOptionsType extends JsonType
{
    public function convertToPHPValue($value, AbstractPlatform $platform): GrantOptionsCollection
    {
        $optionsArray = parent::convertToPHPValue($value, $platform) ?? [];
        $grantOptionsArray = [];
        foreach ($optionsArray as $option) {
            $grantOptionsArray[] = new GrantOption($option['name'], $option['value']);
        }

        return new GrantOptionsCollection(...$grantOptionsArray);
    }

    public function convertToDatabaseValue($options, AbstractPlatform $platform): ?string
    {
        if (is_null($options)) {
            return null;
        }

        if (!$options instanceof GrantOptionsCollection) {
            throw ConversionException::conversionFailedSerialization($options, 'grant_options', 'Invalid argument');
        }

        $optionsArray = 0;
        foreach ($options as $option) {
            $optionsArray[] = [
                'name' => $option->getName(),
                'value' => $option->getValue()
            ];
        }

        return parent::convertToDatabaseValue($optionsArray, $platform);
    }

    public function getName(): string
    {
        return 'grant_options';
    }
}