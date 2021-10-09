<?php

namespace App\Tests\End2End\Controller;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Product;
use App\Entity\Production;
use App\Entity\User;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\AgreementLineFixtureHelpers;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class AgreementLineController extends ApiTestCase
{
    /** @var User */
    private $user;
    /** @var AgreementLineChainFactory */
    private $chainFactory;
    /** @var EntityFactory */
    private $factory;
    /** @var AgreementLineFixtureHelpers */
    private $fixtureHelper;
    /** @var AgreementLine */
    private $agreementLine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new EntityFactory($this->getManager());
        $this->user = $this->factory->make(User::class, [
            'roles' => ['ROLE_ADMIN']
        ]);

        $this->chainFactory = new AgreementLineChainFactory($this->factory);
        $this->fixtureHelper = new AgreementLineFixtureHelpers($this->factory, $this->chainFactory);

        $this->agreementLine = $this->fixtureHelper->makeAgreementLineWithProductionTasks([
            'status' => AgreementLine::STATUS_MANUFACTURING,
            'productionStartDate' => new \DateTime('2021-10-09 12:00:00'),
            'confirmedDate' => new \DateTime('2021-11-09 12:00:00')
        ], [
            'dpt01' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
            'dpt02' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
            'dpt03' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
            'dpt04' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
            'dpt05' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
        ]);

        $this->getManager()->flush();
    }

    public function testShouldAddNewCustomTaskDuringAgreementLineUpdate()
    {
        // Given
        $client = $this->login($this->user);
        $payload = [
            'status' => $this->agreementLine->getStatus(),
            'confirmedDate' => $this->agreementLine->getConfirmedDate()->format('Y-m-d H:i:s'),
            'description' => $this->agreementLine->getDescription(),
            'factor' => $this->agreementLine->getFactor(),
            'tags' => $this->agreementLine->getTags()->toArray(),
            'productions' => array_map(function(Production $production) {
                return [
                    'dateEnd' => $production->getDateEnd()->format('Y-m-d H:i:s'),
                    'dateStart' => $production->getDateStart()->format('Y-m-d H:i:s'),
                    'departmentSlug' => $production->getDepartmentSlug(),
                    'id' => $production->getId(),
                    'status' => $production->getStatus(),
                    'title' => $production->getTitle()
                ];
            }, $this->agreementLine->getProductions()->toArray())
        ];

        $payload['productions'][] = [
            'departmentSlug' => TaskTypes::TYPE_CUSTOM_SLUG,
            'dateStart' => '2021-10-01 12:00:00',
            'dateEnd' => '2021-10-05 13:00:00',
            'id' => null,
            'status' => TaskTypes::TYPE_CUSTOM_STATUS_AWAITS,
            'title' => 'Nowe zadanie'
        ];

        // When
        $client->xmlHttpRequest('PUT', '/agreement_line/update/' . $this->agreementLine->getId(), $payload);

        $this->getManager()->clear();
        /** @var AgreementLine $agreementLineAfter */
        $agreementLineAfter = $this->getManager()->getRepository(AgreementLine::class)
            ->find($this->agreementLine->getId());

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('Nowe zadanie', $agreementLineAfter->getProductions()[5]->getTitle());
        $this->assertEquals('2021-10-01 12:00:00', $agreementLineAfter->getProductions()[5]->getDateStart()->format('Y-m-d H:i:s'));
        $this->assertEquals('2021-10-05 13:00:00', $agreementLineAfter->getProductions()[5]->getDateEnd()->format('Y-m-d H:i:s'));
        $this->assertEquals(
            TaskTypes::TYPE_CUSTOM_STATUS_AWAITS,
            $agreementLineAfter->getProductions()[5]->getStatusLogs()[0]->getCurrentStatus()
        );
    }
}