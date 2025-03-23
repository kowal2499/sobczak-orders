<?php

namespace App\Tests\End2End\Controller;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Entity\User;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\AgreementLineFixtureHelpers;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class AgreementLineControllerTest extends ApiTestCase
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
            'roles' => ['ROLE_PRODUCTION']
        ]);

        $this->chainFactory = new AgreementLineChainFactory($this->factory);
        $this->fixtureHelper = new AgreementLineFixtureHelpers($this->factory, $this->chainFactory);

        $this->getManager()->flush();
    }

    public function testShouldUpdateAgreementLineAndProductionTasks()
    {
        // Given
        $agreementLine = $this->fixtureHelper->makeAgreementLineWithProductionTasks([
            'status' => AgreementLine::STATUS_WAITING,
            'productionStartDate' => new \DateTime('2025-02-15 12:00:00'),
            'confirmedDate' => new \DateTime('2024-12-13 12:01:01'),
            'description' => 'Lorem ipsum',
            'factor' => 0.7
        ], [
            'dpt01' => TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            'dpt02' => TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            'dpt03' => TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            'dpt04' => TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
            'dpt05' => TaskTypes::TYPE_DEFAULT_STATUS_STARTED,
        ]);

        $prods = $agreementLine->getProductions();

        // When
        $payload = [
            'status' => AgreementLine::STATUS_MANUFACTURING,
            'confirmedDate' => '2025-01-15 09:15:00',
            'description' => 'Lorem ipsum updated',
            'productions' => [
                [
                    'id' => $prods[0]->getId(),
                    'departmentSlug' => 'dpt01',
                    'description' => 'dpt01 desc updated',
                    'status' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
                    'dateStart' => '2025-01-01 12:01:01',
                    'dateEnd' => '2025-01-11 12:01:01',
                ],
                [
                    'id' => $prods[1]->getId(),
                    'departmentSlug' => 'dpt02',
                    'description' => 'dpt02 desc updated',
                    'status' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
                    'dateStart' => '2025-01-02 12:01:01',
                    'dateEnd' => '2025-01-12 12:01:01',
                ],
                [
                    'id' => $prods[2]->getId(),
                    'departmentSlug' => 'dpt03',
                    'description' => 'dpt03 desc updated',
                    'status' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
                    'dateStart' => '2025-01-03 12:01:01',
                    'dateEnd' => '2025-01-13 12:01:01',
                ],
                [
                    'id' => $prods[3]->getId(),
                    'departmentSlug' => 'dpt04',
                    'description' => 'dpt04 desc updated',
                    'status' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
                    'dateStart' => '2025-01-04 12:01:01',
                    'dateEnd' => '2025-01-14 12:01:01',
                ],
                [
                    'id' => $prods[4]->getId(),
                    'departmentSlug' => 'dpt05',
                    'description' => 'dpt05 desc updated',
                    'status' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
                    'dateStart' => '2025-01-05 12:01:01',
                    'dateEnd' => '2025-01-15 12:01:01',
                ],
            ]
        ];
        $client = $this->login($this->user);
        $client->xmlHttpRequest(
            'PUT',
            '/agreement_line/update/' . $agreementLine->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        // Then
        $repository = $this->getManager()->getRepository(AgreementLine::class);
        /** @var AgreementLine $updated */
        $updated = $repository->find($agreementLine->getId());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(AgreementLine::STATUS_MANUFACTURING, $updated->getStatus());
        $this->assertEquals('Lorem ipsum updated', $updated->getDescription());
        $this->assertEquals('2025-01-15 09:15:00', $updated->getConfirmedDate()->format('Y-m-d H:i:s'));
        $updatedProd = $updated->getProductions();
        $this->assertCount(5, $updatedProd);

        $this->assertEquals('dpt01', $updatedProd[0]->getDepartmentSlug());
        $this->assertEquals('dpt01 desc updated', $updatedProd[0]->getDescription());
        $this->assertEquals(TaskTypes::TYPE_DEFAULT_STATUS_PENDING, $updatedProd[0]->getStatus());
        $this->assertEquals('2025-01-01 12:01:01', $updatedProd[0]->getDateStart()->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-01-11 12:01:01', $updatedProd[0]->getDateEnd()->format('Y-m-d H:i:s'));

        $this->assertEquals('dpt02', $updatedProd[1]->getDepartmentSlug());
        $this->assertEquals('dpt02 desc updated', $updatedProd[1]->getDescription());
        $this->assertEquals(TaskTypes::TYPE_DEFAULT_STATUS_PENDING, $updatedProd[1]->getStatus());
        $this->assertEquals('2025-01-02 12:01:01', $updatedProd[1]->getDateStart()->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-01-12 12:01:01', $updatedProd[1]->getDateEnd()->format('Y-m-d H:i:s'));

        $this->assertEquals('dpt03', $updatedProd[2]->getDepartmentSlug());
        $this->assertEquals('dpt03 desc updated', $updatedProd[2]->getDescription());
        $this->assertEquals(TaskTypes::TYPE_DEFAULT_STATUS_PENDING, $updatedProd[2]->getStatus());
        $this->assertEquals('2025-01-03 12:01:01', $updatedProd[2]->getDateStart()->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-01-13 12:01:01', $updatedProd[2]->getDateEnd()->format('Y-m-d H:i:s'));

        $this->assertEquals('dpt04', $updatedProd[3]->getDepartmentSlug());
        $this->assertEquals('dpt04 desc updated', $updatedProd[3]->getDescription());
        $this->assertEquals(TaskTypes::TYPE_DEFAULT_STATUS_PENDING, $updatedProd[3]->getStatus());
        $this->assertEquals('2025-01-04 12:01:01', $updatedProd[3]->getDateStart()->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-01-14 12:01:01', $updatedProd[3]->getDateEnd()->format('Y-m-d H:i:s'));

        $this->assertEquals('dpt05', $updatedProd[4]->getDepartmentSlug());
        $this->assertEquals('dpt05 desc updated', $updatedProd[4]->getDescription());
        $this->assertEquals(TaskTypes::TYPE_DEFAULT_STATUS_PENDING, $updatedProd[4]->getStatus());
        $this->assertEquals('2025-01-05 12:01:01', $updatedProd[4]->getDateStart()->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-01-15 12:01:01', $updatedProd[4]->getDateEnd()->format('Y-m-d H:i:s'));
    }

    public function testShouldAddNewCustomTaskDuringAgreementLineUpdate()
    {
        // Given
        $agreementLine = $this->fixtureHelper->makeAgreementLineWithProductionTasks([
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

        $client = $this->login($this->user);
        $payload = [
            'status' => $agreementLine->getStatus(),
            'confirmedDate' => $agreementLine->getConfirmedDate()->format('Y-m-d H:i:s'),
            'description' => $agreementLine->getDescription(),
            'factor' => $agreementLine->getFactor(),
            'tags' => $agreementLine->getTags()->toArray(),
            'productions' => array_map(function (Production $production) {
                return [
                    'dateEnd' => $production->getDateEnd()->format('Y-m-d H:i:s'),
                    'dateStart' => $production->getDateStart()->format('Y-m-d H:i:s'),
                    'departmentSlug' => $production->getDepartmentSlug(),
                    'id' => $production->getId(),
                    'status' => $production->getStatus(),
                    'title' => $production->getTitle()
                ];
            }, $agreementLine->getProductions()->toArray())
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
        $client->xmlHttpRequest(
            'PUT',
            '/agreement_line/update/' . $agreementLine->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $this->getManager()->clear();
        /** @var AgreementLine $agreementLineAfter */
        $agreementLineAfter = $this->getManager()->getRepository(AgreementLine::class)
            ->find($agreementLine->getId());

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