<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory\Definition;

use App\Entity\Agreement;
use App\Tests\Utilities\Factory\FactoryDefinitionInterface;
use Faker\Generator;

class AgreementFactory implements FactoryDefinitionInterface
{
    public static function supports(): string
    {
        return Agreement::class;
    }

    public function defaultProperties(Generator $faker): array
    {
        return [
            'createDate' => new \DateTime(),
            'updateDate' => new \DateTime(),
            'orderNumber' => $faker->randomNumber(5),
            'status' => 0
        ];
    }
}