<?php

namespace App\Tests\Unit\Service\AgreementLine;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Entity\StatusLog;
use App\Service\AgreementLine\ProductionCompletionDateResolverService;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class ProductionCompletionDateResolverTest extends TestCase
{
    /** @var ProductionCompletionDateResolverService */
    private $serviceUnderTest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceUnderTest = new ProductionCompletionDateResolverService();
    }

    public function testShouldReturnNullIfThereAreNoProductionTasks()
    {
        // Given
        $productions = new ArrayCollection();
        // When
        $productionDate = $this->serviceUnderTest->getCompletionDate($productions);
        // Then
        $this->assertNull($productionDate);
    }

    public function testShouldReturnNullIfAnyTaskIsNotCompletedOrNotApplicable()
    {
        // Given
        $agreementLine = new AgreementLine();

        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_GLUING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, []));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_CNC, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, []));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING, TaskTypes::TYPE_DEFAULT_STATUS_AWAITS, []));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, []));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, []));

        // When
        $productionDate = $this->serviceUnderTest->getCompletionDate($agreementLine->getProductions());
        // Then
        $this->assertNull($productionDate);
    }

    public function testShouldReturnDateIfAllTasksAreCompleted()
    {
        // Given
        $statusLog0 = new StatusLog();
        $statusLog0->setCreatedAt(new \DateTime('2021-09-15 12:19:11'));
        $statusLog0->setCurrentStatus(TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED);

        $agreementLine = new AgreementLine();

        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_GLUING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, []));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_CNC, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, [$statusLog0]));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, []));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, []));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, []));

        // When
        $productionDate = $this->serviceUnderTest->getCompletionDate($agreementLine->getProductions());
        // Then
        $this->assertEquals('2021-09-15 12:19:11', $productionDate->format('Y-m-d H:i:s'));
    }

    public function testShouldReturnDateIfAllTasksAreCompletedOrNA()
    {
        // Given
        $statusLog0 = new StatusLog();
        $statusLog0->setCreatedAt(new \DateTime('2021-09-12 12:19:11'));
        $statusLog0->setCurrentStatus(TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED);
        $statusLog1 = new StatusLog();
        $statusLog1->setCreatedAt(new \DateTime('2021-09-16 12:19:11'));
        $statusLog1->setCurrentStatus(TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE);

        $agreementLine = new AgreementLine();

        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_GLUING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, []));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_CNC, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, [$statusLog0]));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING, TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE, [$statusLog1]));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING, TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE, []));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING, TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE, []));

        // When
        $productionDate = $this->serviceUnderTest->getCompletionDate($agreementLine->getProductions());
        // Then
        $this->assertEquals('2021-09-12 12:19:11', $productionDate->format('Y-m-d H:i:s'));
    }

    public function testShouldReturnNullIfAllTasksAreNA()
    {
        // Given
        $statusLog0 = new StatusLog();
        $statusLog0->setCreatedAt(new \DateTime('2021-09-12 12:19:11'));
        $statusLog0->setCurrentStatus(TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE);
        $statusLog1 = new StatusLog();
        $statusLog1->setCreatedAt(new \DateTime('2021-09-16 12:19:11'));
        $statusLog1->setCurrentStatus(TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE);

        $agreementLine = new AgreementLine();

        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_GLUING, TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE, []));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_CNC, TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE, [$statusLog0]));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING, TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE, [$statusLog1]));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING, TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE, []));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING, TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE, []));

        // When
        $productionDate = $this->serviceUnderTest->getCompletionDate($agreementLine->getProductions());
        // Then
        $this->assertNull($productionDate);
    }


    public function testShouldReturnLatestStatusLatestCompletionDateOfTaskOfAnyType()
    {
        // Given
        $statusLog0 = new StatusLog();
        $statusLog0->setCreatedAt(new \DateTime('2021-09-13 12:20:11'));
        $statusLog0->setCurrentStatus(TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED);

        $statusLog1 = new StatusLog();
        $statusLog1->setCreatedAt(new \DateTime('2021-09-10 12:19:11'));
        $statusLog1->setCurrentStatus(TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED);

        $statusLog2 = new StatusLog();
        $statusLog2->setCreatedAt(new \DateTime('2021-09-13 12:20:12'));
        $statusLog2->setCurrentStatus(TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED);

        $statusLog3 = new StatusLog();
        $statusLog3->setCreatedAt(new \DateTime('2021-09-11 12:19:11'));
        $statusLog3->setCurrentStatus(TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED);

        $statusLog4 = new StatusLog();
        $statusLog4->setCreatedAt(new \DateTime('2021-09-12 12:19:11'));
        $statusLog4->setCurrentStatus(TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED);

        $agreementLine = new AgreementLine();
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_GLUING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, [$statusLog0]));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_CNC, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, [$statusLog1]));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_GRINDING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, [$statusLog2]));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_VARNISHING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, [$statusLog3, $statusLog4]));
        $agreementLine->addProduction($this->createProductionTask(TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, []));
        // When
        $productionDate = $this->serviceUnderTest->getCompletionDate($agreementLine->getProductions());
        // Then
        $this->assertEquals('2021-09-13 12:20:12', $productionDate->format('Y-m-d H:i:s'));
    }

    /**
     * @param string $department
     * @param string $status
     * @param StatusLog[] $logs
     * @return Production
     */
    private function createProductionTask(string $department, string $status, array $logs): Production
    {
        $productionTask = new Production();
        $productionTask->setDepartmentSlug($department);
        $productionTask->setStatus((int)$status);
        foreach ($logs as $log) {
            $productionTask->addStatusLog($log);
        }
        return $productionTask;
    }
}