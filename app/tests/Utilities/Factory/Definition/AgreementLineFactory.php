<?php

namespace App\Tests\Utilities\Factory\Definition;

use App\Entity\AgreementLine;
use App\Tests\Utilities\Factory\FactoryDefinitionInterface;
use Faker\Generator;

class AgreementLineFactory implements FactoryDefinitionInterface
{
    public static function supports(): string
    {
        return AgreementLine::class;
    }

    public function defaultProperties(Generator $faker): array
    {
        return [
            'archived' => false,
            'deleted' => false,
            'confirmedDate' => new \DateTime(),
            'status' => 0,
            'factor' => $faker->randomFloat(2, 0, 1)
        ];
    }
}