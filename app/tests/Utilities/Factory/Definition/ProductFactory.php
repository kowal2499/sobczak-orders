<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory\Definition;

use App\Entity\Product;
use App\Tests\Utilities\Factory\FactoryDefinitionInterface;
use Faker\Generator;

class ProductFactory implements FactoryDefinitionInterface
{
    public static function supports(): string
    {
        return Product::class;
    }

    public function defaultProperties(Generator $faker): array
    {
        return [
            'name' => $faker->word,
            'description' => $faker->sentence(5),
            'createDate' => new \DateTime(),
            'factor' => $faker->randomFloat(2, 0, 1)
        ];
    }
}