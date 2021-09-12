<?php

namespace App\Tests\Utilities;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Tests\Utilities\Factory\EntityFactory;

class ProductionFixtureHelpers
{
    /** @var EntityFactory */
    private $factory;

    public function __construct(EntityFactory $factory)
    {
        $this->factory = $factory;
    }

    public function makeProductionTasks(array $tasksProps): array
    {
        $tasks = [];
        foreach ($tasksProps as $prop) {
            /** @var Production $production */
            $tasks[] = $this->factory->make(Production::class, $prop);
        }
        return $tasks;
    }
    public function getArrayOfProps(AgreementLine $agreementLine, array $tasksProps = []): array
    {
        $propsWithDepartment = [];
        foreach (TaskTypes::getDefaultSlugs() as $slug) {
            /** @var Production $productionTask */
            $propsWithDepartment[] = array_merge([
                'agreementLine' => $agreementLine,
                'departmentSlug' => $slug,
            ], $tasksProps);
        }
        return $propsWithDepartment;
    }
}