<?php

namespace App\Tests\End2End\Modules\Reports;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Modules\Reports\Production\ProductionReport;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportUnderTest = new ProductionReport(
            $this->getManager()->getRepository(AgreementLine::class)
        );
        $factory = new EntityFactory($this->getManager());
        $this->agreementLineChainFactory = new AgreementLineChainFactory($factory);
        $this->agreementLineFixturesHelper = new AgreementLineFixtureHelpers(
            $factory,
            $this->agreementLineChainFactory
        );
    }

    public function testShouldGetReportData()
    {
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        $department = [];
        // When
        $result = $this->reportUnderTest->calc($dateStart, $dateEnd, $department);
        // Then
        $this->assertArrayHasKey('orders_pending', $result);
        $this->assertArrayHasKey('orders_finished', $result);
    }

    public function testShouldGetProductionFinishedData()
    {
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        $department = [];
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
        $result = $this->reportUnderTest->calc($dateStart, $dateEnd, $department);
        // Then
        $this->assertCount(2, $result['orders_finished']['data']);
    }

    public function testShouldGetZeroFinishedAgreementLinesWhenNoAgreementLineIsFinished()
    {
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        $this->agreementLineChainFactory->make(['createDate' => new \DateTime('2021-09-15')], []);
        // When
        $result = $this->reportUnderTest->calc($dateStart, $dateEnd);
        $resultWithDpt = $this->reportUnderTest->calc($dateStart, $dateEnd, [TaskTypes::TYPE_DEFAULT_SLUG_GLUING]);
        // Then
        $this->assertCount(0, $result['orders_finished']['data']);
        $this->assertCount(0, $resultWithDpt['orders_finished']['data']);
    }
    public function testShouldGetFinishedAgreementLinesInDepartmentContext()
    {
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        $department = [TaskTypes::TYPE_DEFAULT_SLUG_GLUING];
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime(), 'productionCompletionDate' => new \DateTime('2021-08-10')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime(), 'productionCompletionDate' => new \DateTime('2021-09-10')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime(), 'productionCompletionDate' => new \DateTime('2021-09-15')], [
            TaskTypes::TYPE_DEFAULT_SLUG_GLUING => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
        ]);
        // When
        $result = $this->reportUnderTest->calc($dateStart, $dateEnd, $department);
        // Then
        $this->assertCount(1, $result['orders_finished']['data']);
    }

    public function testShouldGetProductionPendingData()
    {
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        $department = [];
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-08-30')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-09-15')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-09-20')]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-09-20'),
            'productionCompletionDate' => new \DateTime('2021-09-22'),
        ]);
        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-10-01')]);
        // When
        $result = $this->reportUnderTest->calc($dateStart, $dateEnd, $department);
        // Then
        $this->assertCount(2, $result['orders_pending']['data']);
    }

    public function testShouldGetProductionPendingInDepartmentContext()
    {
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        $departments = [TaskTypes::TYPE_DEFAULT_SLUG_GLUING];

        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-08-30')
        ], [TaskTypes::TYPE_DEFAULT_SLUG_GLUING => TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE]);

        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-09-15')
        ], [TaskTypes::TYPE_DEFAULT_SLUG_GLUING => TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE]);

        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-09-20')
        ], [TaskTypes::TYPE_DEFAULT_SLUG_GLUING => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED]);

        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks(['productionStartDate' => new \DateTime('2021-10-01')]);
        // When
        $result = $this->reportUnderTest->calc($dateStart, $dateEnd, $departments);
        // Then
        $this->assertCount(1, $result['orders_pending']['data']);
    }

    public function testShouldGetProductionPendingForManyDepartments()
    {
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        $departments = [
            TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
            TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING,
            TaskTypes::TYPE_DEFAULT_SLUG_GRINDING
        ];

        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-09-20')
        ], [
            TaskTypes::TYPE_DEFAULT_SLUG_GLUING => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            TaskTypes::TYPE_DEFAULT_SLUG_GRINDING => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
        ]);

        // When
        $result = $this->reportUnderTest->calc($dateStart, $dateEnd, $departments);

        // Then
        $this->assertCount(1, $result['orders_pending']['data']);
    }

    public function testShouldGetProductionFinishedForManyDepartments()
    {
        // Given
        $dateStart = new \DateTime('2021-09-01');
        $dateEnd = new \DateTime('2021-09-30');
        $departments = [
            TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
            TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING,
            TaskTypes::TYPE_DEFAULT_SLUG_GRINDING
        ];

        $this->agreementLineFixturesHelper->makeAgreementLineWithProductionTasks([
            'productionStartDate' => new \DateTime('2021-09-20'),
            'productionCompletionDate' => new \DateTime('2021-09-25'),
        ], [
            TaskTypes::TYPE_DEFAULT_SLUG_GLUING => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            TaskTypes::TYPE_DEFAULT_SLUG_GRINDING => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
        ]);

        // When
        $result = $this->reportUnderTest->calc($dateStart, $dateEnd, $departments);

        // Then
        $this->assertCount(1, $result['orders_finished']['data']);
    }


}