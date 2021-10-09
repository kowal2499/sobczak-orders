<?php

namespace App\Tests\Utilities;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class AgreementLineFixtureHelpers
{
    private $factory;
    private $agreementLineChainFactory;
    /** @var ProductionFixtureHelpers */
    private $productionFixtures;

    public function __construct(EntityFactory $factory, AgreementLineChainFactory $agreementLineChainFactory)
    {
        $this->factory = $factory;
        $this->agreementLineChainFactory = $agreementLineChainFactory;
        $this->productionFixtures = new ProductionFixtureHelpers($factory);
    }

    public function makeAgreementLineWithProductionTasks(
        $agreementLineProps = [],
        $prodStatuses = []): AgreementLine
    {
        $agreementLine = $this->agreementLineChainFactory->make([], $agreementLineProps);

        $prodProps = [];
        foreach ($this->productionFixtures->getArrayOfProps($agreementLine) as $prop) {
            $prop['status'] = $prodStatuses[$prop['departmentSlug']] ?? TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED;
            $prodProps[] = $prop;
        }
        foreach ($this->productionFixtures->makeProductionTasks($prodProps) as $task) {
            $agreementLine->addProduction($task);
        }

//        dd($tasks);
        $this->factory->flush();
        $this->factory->clear();

        return $agreementLine;
    }
}