<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Common\Persistence\ObjectManager;

class ProductFixtures extends BaseFixture
{

    static $productNames = [
        'Stół drewniany', 'Krzesło małe', 'Regał mały', 'Regał duży', 'Stół z bali', 'Regał średni', 'Krzesło duże',
        'Stół z płyty laminowany', 'Stół średni', 'Krzesło drewniane', 'Krzesło z płyty', 'Schody', 'Parapet',
    ];

    protected function loadData(ObjectManager $manager)
    {
        foreach (self::$productNames as $item) {
            $product = new Product();
            $product
                ->setName($item)
                ->setDescription($this->faker->paragraph)
                ->setFactor($this->faker->randomFloat(2, 0, 1))
                ->setCreateDate($this->faker->dateTimeBetween('-123 days', '-1 days'))
            ;
            $manager->persist($product);

        }


        $manager->flush();
    }
}
