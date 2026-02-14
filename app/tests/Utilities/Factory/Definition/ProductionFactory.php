<?php

namespace App\Tests\Utilities\Factory\Definition;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Tests\Utilities\Factory\FactoryDefinitionInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Faker\Generator;

class ProductionFactory implements FactoryDefinitionInterface
{
    public static function supports(): string
    {
        return Production::class;
    }

    public function defaultProperties(Generator $faker): array
    {
        return [
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
            'dateStart' => new \DateTime(),
            'dateEnd' => new \DateTime(),
            'status' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime(),
            'description' => '',
            'title' => '',
            'isStartDelayed' => false,
            'isCompleted' => false,
        ];
    }
}