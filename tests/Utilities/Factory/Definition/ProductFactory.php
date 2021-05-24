<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory\Definition;


use App\Entity\Product;
use App\Tests\Utilities\Factory\FactoryDefinitionInterface;
use Faker\Generator;

class ProductFactory implements FactoryDefinitionInterface
{
    public function define(Generator $faker, callable $callback = null): Product
    {
        $product = new Product();
        $product
            ->setName($faker->word)
            ->setDescription($faker->sentence(5))
            ->setCreateDate(new \DateTime())
            ->setFactor(0.8);

        return $product;
    }

    public static function supports(): string
    {
        return Product::class;
    }
}