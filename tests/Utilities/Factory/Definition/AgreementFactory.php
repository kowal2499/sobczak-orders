<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory\Definition;

use App\Entity\Agreement;
use App\Tests\Utilities\Factory\FactoryDefinitionInterface;
use Faker\Generator;

class AgreementFactory implements FactoryDefinitionInterface
{
    public function define(Generator $faker, callable $callback = null): Agreement
    {
        if (!$callback) {
            throw new \Exception('Callback is required and has to set Customer object');
        }
        $agreement = new Agreement();
        $callback($agreement);
        $agreement
            ->setUpdateDate(new \DateTime())
            ->setCreateDate(new \DateTime())
            ->setOrderNumber($faker->randomNumber(5))
            ->setStatus(0);
        return $agreement;
    }

    public static function supports(): string
    {
        return Agreement::class;
    }
}