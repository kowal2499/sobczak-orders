<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory\Definition;

use App\Module\Production\Entity\FactorAdjust;
use App\Tests\Utilities\Factory\FactoryDefinitionInterface;
use Faker\Generator;

class FactorAdjustFactory implements FactoryDefinitionInterface
{

    public static function supports(): string
    {
        return FactorAdjust::class;
    }

    public function defaultProperties(Generator $faker): array
    {
        return [
            'description' => $faker->sentence(),
            'factor' => $faker->randomFloat(2, 0.1, 1.2)
        ];
    }
}