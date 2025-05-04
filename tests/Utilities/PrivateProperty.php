<?php

namespace App\Tests\Utilities;

use ReflectionClass;

class PrivateProperty
{
    public static function setId(int $value, object $object): void
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}