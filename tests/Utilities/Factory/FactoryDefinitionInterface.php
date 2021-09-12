<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory;

use Faker\Generator;

interface FactoryDefinitionInterface
{
    /**
     * @param Generator $faker
     * @return array
     */
    public function defaultProperties(Generator $faker): array;
    /**
     * @return string
     */
    public static function supports(): string;
}