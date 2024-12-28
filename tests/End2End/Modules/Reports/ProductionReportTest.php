<?php

namespace App\Tests\End2End\Modules\Reports;

use App\Entity\Definitions\TaskTypes;
use App\Entity\User;
use App\Modules\Reports\Production\ProductionReport;
use App\Modules\Reports\Production\Repository\DoctrineProductionFinishedRepository;
use App\Modules\Reports\Production\Repository\DoctrineProductionPendingRepository;
use App\Modules\Reports\Production\Repository\DoctrineProductionTasksRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\AgreementLineFixtureHelpers;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class ProductionReportTest extends ApiTestCase
{
    private $reportUnderTest;
    /** @var AgreementLineFixtureHelpers */
    private $agreementLineFixturesHelper;
    /** @var AgreementLineChainFactory */
    private $agreementLineChainFactory;
    /** @var User */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $manager = $this->getManager();
        $factory = new EntityFactory($manager);
        $this->user = $factory->make(User::class, [
            'roles' => ['ROLE_ADMIN']
        ]);
        $this->login($this->user);

        $this->agreementLineChainFactory = new AgreementLineChainFactory($factory);
        $this->agreementLineFixturesHelper = new AgreementLineFixtureHelpers(
            $factory,
            $this->agreementLineChainFactory
        );
        /** @var DoctrineProductionPendingRepository $pendingRepository */
        $pendingRepository = $this->get(DoctrineProductionPendingRepository::class);
        /** @var DoctrineProductionFinishedRepository $finishedRepository */
        $finishedRepository = $this->get(DoctrineProductionFinishedRepository::class);

        $this->reportUnderTest = new ProductionReport(
            $pendingRepository,
            $finishedRepository,
            $this->createMock(DoctrineProductionTasksRepository::class)
        );
    }

    public function testShouldGetReportData()
    {
        $this->markTestSkipped();
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        // When
        $result = $this->reportUnderTest->getSummary($dateStart, $dateEnd);
        // Then
        $this->assertArrayHasKey('orders_pending', $result);
        $this->assertArrayHasKey('orders_finished', $result);
    }

    public function testShouldGetProductionFinishedData()
    {
        $this->markTestSkipped();
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime(), 'productionCompletionDate' => new \DateTime('2021-08-30')
        ]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime(), 'productionCompletionDate' => new \DateTime('2021-09-15')
        ]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime(), 'productionCompletionDate' => new \DateTime('2021-09-20')
        ]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime(), 'productionCompletionDate' => new \DateTime('2021-10-01')
        ]);
        // When
        $result = $this->reportUnderTest->getSummary($dateStart, $dateEnd);
        // Then
        $this->assertEquals(2, $result['orders_finished'][0]['count']);
    }

    public function testShouldGetZeroFinishedAgreementLinesWhenNoAgreementLineIsFinished()
    {
        $this->markTestSkipped();
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        $this->agreementLineChainFactory->make(['createDate' => new \DateTime('2021-09-15')], []);
        // When
        $result = $this->reportUnderTest->getSummary($dateStart, $dateEnd);
        // Then
        $this->assertEquals(0, $result['orders_finished'][0]['count']);
    }
    public function testShouldGetFinishedAgreementLinesWithDepartmentsInvolvement()
    {
        $this->markTestSkipped();
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-08-01'), 'productionCompletionDate' => new \DateTime('2021-08-10')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-09-01'), 'productionCompletionDate' => new \DateTime('2021-09-10')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-09-10'), 'productionCompletionDate' => new \DateTime('2021-09-15')], [
            TaskTypes::TYPE_DEFAULT_SLUG_GLUING => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
        ]);
        // When
        $result = $this->reportUnderTest->getOrdersFinishedDetails($dateStart, $dateEnd);
        // Then
        $this->assertEquals(1, $result[0]['involved_dpt01']);
        $this->assertEquals(1, $result[0]['involved_dpt02']);
        $this->assertEquals(1, $result[0]['involved_dpt03']);
        $this->assertEquals(1, $result[0]['involved_dpt04']);
        $this->assertEquals(1, $result[0]['involved_dpt05']);

        $this->assertEquals(0, $result[1]['involved_dpt01']);
        $this->assertEquals(1, $result[1]['involved_dpt02']);
        $this->assertEquals(1, $result[1]['involved_dpt03']);
        $this->assertEquals(1, $result[1]['involved_dpt04']);
        $this->assertEquals(1, $result[1]['involved_dpt05']);
    }

    public function testShouldGetProductionPendingData()
    {
        $this->markTestSkipped();
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-08-30')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-09-15')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-09-20')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-09-20'),
            'productionCompletionDate' => new \DateTime('2021-09-22'),
        ]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-08-20'),
            'productionCompletionDate' => new \DateTime('2021-09-22'),
        ]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-10-01')]);
        // When
        $result = $this->reportUnderTest->getSummary($dateStart, $dateEnd);
        // Then
        $this->assertEquals(3, $result['orders_pending'][0]['count']);
    }

    public function testShouldGetPendingAgreementLinesWithDepartmentsInvolvement()
    {
        $this->markTestSkipped();
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');

        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-08-30')
        ], [TaskTypes::TYPE_DEFAULT_SLUG_GLUING => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS]);

        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-09-15')
        ], [TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING => TaskTypes::TYPE_DEFAULT_STATUS_STARTED]);

        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-09-20')
        ], [TaskTypes::TYPE_DEFAULT_SLUG_GLUING => TaskTypes::TYPE_DEFAULT_STATUS_PENDING]);

        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-10-01')]);
        // When
        $result = $this->reportUnderTest->getOrdersPendingDetails($dateStart, $dateEnd);
        // Then
        $this->assertEquals(0, $result[0]['involved_dpt01']);
        $this->assertEquals(1, $result[0]['involved_dpt02']);
        $this->assertEquals(1, $result[0]['involved_dpt03']);
        $this->assertEquals(1, $result[0]['involved_dpt04']);
        $this->assertEquals(1, $result[0]['involved_dpt05']);

        $this->assertEquals(1, $result[1]['involved_dpt01']);
        $this->assertEquals(1, $result[1]['involved_dpt02']);
        $this->assertEquals(1, $result[1]['involved_dpt03']);
        $this->assertEquals(0, $result[1]['involved_dpt04']);
        $this->assertEquals(1, $result[1]['involved_dpt05']);

        $this->assertEquals(0, $result[2]['involved_dpt01']);
        $this->assertEquals(1, $result[2]['involved_dpt02']);
        $this->assertEquals(1, $result[2]['involved_dpt03']);
        $this->assertEquals(1, $result[2]['involved_dpt04']);
        $this->assertEquals(1, $result[2]['involved_dpt05']);
    }
}