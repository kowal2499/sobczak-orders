<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory;

use Faker\Generator;

interface FactoryDefinitionInterface
{
    /**
     * @param Generator $faker
     * @param callable|null $callback
     * @return mixed
     */
    public function define(Generator $faker, callable $callback = null);

    /**
     * @return string
     */
    public static function supports(): string;
}