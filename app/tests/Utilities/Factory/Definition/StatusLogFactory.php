<?php

namespace App\Tests\Utilities\Factory\Definition;

use App\Entity\StatusLog;
use App\Tests\Utilities\Factory\FactoryDefinitionInterface;
use Faker\Generator;

class StatusLogFactory implements FactoryDefinitionInterface
{
    public function defaultProperties(Generator $faker): array
    {
        return [
            'createdAt' => new \DateTime()
        ];
    }

    public static function supports(): string
    {
        return StatusLog::class;
    }
}