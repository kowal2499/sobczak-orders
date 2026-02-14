<?php

namespace App\Tests\Reports\Production\Integration;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Entity\StatusLog;
use App\Entity\User;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

/**
 * @deprecated
 */
class DoctrineProductionModelRepositoryTest extends ApiTestCase
{
    /** @var EntityFactory */
    private $factory;
    /** @var AgreementLineChainFactory */
    private $agreementLineChanFactory;
    /** @var User */
    private $user;
    /** @var \Symfony\Bundle\FrameworkBundle\KernelBrowser */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new EntityFactory($this->getManager());
        $this->agreementLineChanFactory = new AgreementLineChainFactory($this->factory);
        $this->user = $this->factory->make(User::class, [
            'roles' => ['ROLE_ADMIN']
        ]);
        $this->factory->flush();
        $this->client = $this->login($this->user);
    }

    public function testShouldCountOrdersInProductionIfDpt05TaskWasCreatedBeforeEndOfCurrentMonth()
    {
        $this->markTestSkipped();
        $agreementLineParams = [
            'status' => AgreementLine::STATUS_WAITING,
        ];
        // Given
        $agreementLine1 = $this->agreementLineChanFactory->make([], $agreementLineParams);
        $agreementLine2 = $this->agreementLineChanFactory->make([], $agreementLineParams);
        $agreementLine3 = $this->agreementLineChanFactory->make([], $agreementLineParams);

        $this->makeProductionTasks($this->getArrayOfProps($agreementLine1, ['createdAt' => new \DateTime('2021-08-10')]));
        $this->makeProductionTasks($this->getArrayOfProps($agreementLine2, ['createdAt' => new \DateTime('2021-09-10')]));
        $this->makeProductionTasks($this->getArrayOfProps($agreementLine3, ['createdAt' => new \DateTime('2021-10-10')]));
        $this->factory->flush();

        // When
        $this->client->xmlHttpRequest('POST', '/production/summary', [
            'month' => 9,
            'year' => 2021
        ]);
        // Then
        $this->assertEquals(
            2,
            json_decode($this->client->getResponse()->getContent(), true)['production']['ordersInProduction']
        );
    }

    public function testShouldCountOrdersInProductionIfAgreementLineStatusIsWaitingOrInManufacturing()
    {
        $this->markTestSkipped();
        // Given
        $agreementLine1 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_WAITING]);
        $agreementLine2 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_MANUFACTURING]);
        $agreementLine3 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_ARCHIVED]);
        $agreementLine4 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_DELETED]);
        $agreementLine5 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_WAREHOUSE]);

        $this->makeProductionTasks($this->getArrayOfProps($agreementLine1, ['createdAt' => new \DateTime('2021-09-10')]));
        $this->makeProductionTasks($this->getArrayOfProps($agreementLine2, ['createdAt' => new \DateTime('2021-09-10')]));
        $this->makeProductionTasks($this->getArrayOfProps($agreementLine3, ['createdAt' => new \DateTime('2021-09-10')]));
        $this->makeProductionTasks($this->getArrayOfProps($agreementLine4, ['createdAt' => new \DateTime('2021-09-10')]));
        $this->makeProductionTasks($this->getArrayOfProps($agreementLine5, ['createdAt' => new \DateTime('2021-09-10')]));
        $this->factory->flush();

        // When
        $this->client->xmlHttpRequest('POST', '/production/summary', [
            'month' => 9,
            'year' => 2021
        ]);
        // Then
        $this->assertEquals(
            2,
            json_decode($this->client->getResponse()->getContent(), true)['production']['ordersInProduction']
        );
    }

    public function testShouldCountOrdersInProductionIfDpt05StatusInOtherThenCompleted()
    {
        $this->markTestSkipped();
        // Given
        $agreementLine1 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_MANUFACTURING]);
        $agreementLine2 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_MANUFACTURING]);
        $agreementLine3 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_MANUFACTURING]);
        $agreementLine4 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_MANUFACTURING]);
        $agreementLine5 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_MANUFACTURING]);

        $commonProps = [
            'status' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            'createdAt' => new \DateTime('2021-09-10'),
        ];

        $props1 = $this->getArrayOfProps($agreementLine1, $commonProps);
        $props2 = $this->getArrayOfProps($agreementLine2, $commonProps);
        $props3 = $this->getArrayOfProps($agreementLine3, $commonProps);
        $props4 = $this->getArrayOfProps($agreementLine4, $commonProps);
        $props5 = $this->getArrayOfProps($agreementLine5, $commonProps);

        $props1[4]['status'] = TaskTypes::TYPE_DEFAULT_STATUS_AWAITS;
        $props2[4]['status'] = TaskTypes::TYPE_DEFAULT_STATUS_STARTED;
        $props3[4]['status'] = TaskTypes::TYPE_DEFAULT_STATUS_PENDING;
        $props4[4]['status'] = TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED;
        $props5[4]['status'] = TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE;

        $this->makeProductionTasks($props1);
        $this->makeProductionTasks($props2);
        $this->makeProductionTasks($props3);
        $this->makeProductionTasks($props4);
        $this->makeProductionTasks($props5);

        $this->factory->flush();

        // When
        $this->client->xmlHttpRequest('POST', '/production/summary', [
            'month' => 9,
            'year' => 2021
        ]);
        // Then
        $this->assertEquals(
            4,
            json_decode($this->client->getResponse()->getContent(), true)['production']['ordersInProduction']
        );
    }

    public function testShouldCountOrdersAsFinishedIfPackingStatusIsCompletedAndLogStatusIsCompletedAndWasSetInCurrentMonth()
    {
        $this->markTestSkipped();
        // Given
        $agreementLine1 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_MANUFACTURING]);
        $agreementLine2 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_MANUFACTURING]);
        $agreementLine3 = $this->agreementLineChanFactory->make([], ['status' => AgreementLine::STATUS_MANUFACTURING]);

        $productions1 = $this->makeProductionTasks($this->getArrayOfProps($agreementLine1, [
            'status' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            'createdAt' => new \DateTime('2021-08-10'),
        ]));
        $productions2 = $this->makeProductionTasks($this->getArrayOfProps($agreementLine2, [
            'status' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            'createdAt' => new \DateTime('2021-09-10'),
        ]));
        $productions3 = $this->makeProductionTasks($this->getArrayOfProps($agreementLine3, [
            'status' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
            'createdAt' => new \DateTime('2021-09-10'),
        ]));

        $this->factory->make(StatusLog::class, [
            'production' => $productions1[4],
            'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            'createdAt' => new \DateTime('2021-08-15'),
            'user' => $this->user
        ]);
        $this->factory->make(StatusLog::class, [
            'production' => $productions2[4],
            'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            'createdAt' => new \DateTime('2021-09-15'),
            'user' => $this->user
        ]);
        $this->factory->make(StatusLog::class, [
            'production' => $productions3[4],
            'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
            'createdAt' => new \DateTime('2021-09-15'),
            'user' => $this->user
        ]);
        $this->factory->flush();

        // When
        $this->client->xmlHttpRequest('POST', '/production/summary', [
            'month' => 9,
            'year' => 2021
        ]);
        // Then

        $this->assertEquals(
            1,
            json_decode($this->client->getResponse()->getContent(), true)['production']['ordersFinished']
        );
    }

    private function makeProductionTasks(array $tasksProps): array
    {
        $tasks = [];
        foreach ($tasksProps as $prop) {
            /** @var Production $productionTask */
            $tasks[] = $this->factory->make(Production::class, $prop);
        }
        return $tasks;
    }
    private function getArrayOfProps(AgreementLine $agreementLine, array $tasksProps = []): array
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