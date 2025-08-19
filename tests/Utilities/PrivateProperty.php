<?php

namespace App\Tests\Utilities;

use ReflectionClass;

class PrivateProperty
{
    private static int $lastId = 1;
    public static function setId(object $object, ?int $value = null): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        if ($value === null) {
            $value = self::$lastId++;
        }
        $property->setValue($object, $value);
    }
}