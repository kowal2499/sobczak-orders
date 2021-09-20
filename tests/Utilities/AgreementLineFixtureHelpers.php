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

    public function makeFinishedAgreementLine(string $productionCompletionDate, $prodStatuses = [], bool $deleted = false): AgreementLine
    {
        $agreementLine = $this->agreementLineChainFactory->make([], [
            'productionCompletionDate' => new \DateTime($productionCompletionDate),
            'deleted' => $deleted
        ]);

        $prodProps = [];
        foreach ($this->productionFixtures->getArrayOfProps($agreementLine) as $idx => $prop) {
            $prop['status'] = $prodStatuses[$prop['departmentSlug']] ?? TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED;
            $prodProps[] = $prop;
        }
        $this->productionFixtures->makeProductionTasks($prodProps);

        $this->factory->flush();
        $this->factory->clear();

        return $agreementLine;
    }
}