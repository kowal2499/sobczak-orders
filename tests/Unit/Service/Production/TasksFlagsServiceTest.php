<?php

namespace App\Tests\Unit\Service\Production;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Exceptions\Production\StatusNotMatchWithTaskTypeException;
use App\Service\Production\TaskStatusService;
use PHPUnit\Framework\TestCase;

class TasksFlagsServiceTest extends TestCase
{
    /** @var TaskStatusService */
    private $serviceUnderTest;

    protected function setUp(): void
    {
        $this->serviceUnderTest = new TaskStatusService();
    }

    public function testShouldThrowExceptionWhenStatusDoNotMatchWithTaskTypeDefault()
    {
        // Except
        $this->expectException(StatusNotMatchWithTaskTypeException::class);
        // Given
        $production = new Production();
        $production->setDepartmentSlug(TaskTypes::TYPE_DEFAULT_SLUG_GLUING);
        // When & Then
        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_CUSTOM_STATUS_AWAITS);
    }

    public function testShouldThrowExceptionWhenStatusDoNotMatchWithTaskTypeCustom()
    {
        // Except
        $this->expectException(StatusNotMatchWithTaskTypeException::class);
        // Given
        $production = new Production();
        $production->setDepartmentSlug(TaskTypes::TYPE_CUSTOM_SLUG);
        // When & Then
        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_AWAITS);
    }

    public function testShouldConfirmCompletionWhenStatusIsSetAsCompletedAndTaskTypeIsDefault()
    {
        // Given
        $production = new Production();
        $production->setDepartmentSlug(TaskTypes::TYPE_DEFAULT_SLUG_GLUING);
        // When & Then
        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED);
        $this->assertEquals(true, $production->getIsCompleted());

        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE);
        $this->assertEquals(false, $production->getIsCompleted());

        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_PENDING);
        $this->assertEquals(false, $production->getIsCompleted());

        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_AWAITS);
        $this->assertEquals(false, $production->getIsCompleted());

        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_STARTED);
        $this->assertEquals(false, $production->getIsCompleted());
    }

    public function testShouldConfirmCompletionWhenStatusIsSetAsCompletedAndTaskTypeIsCustom()
    {
        // Given
        $production = new Production();
        $production->setDepartmentSlug(TaskTypes::TYPE_CUSTOM_SLUG);
        // When & Then
        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_CUSTOM_STATUS_COMPLETED);
        $this->assertEquals(true, $production->getIsCompleted());

        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_CUSTOM_STATUS_AWAITS);
        $this->assertEquals(false, $production->getIsCompleted());

        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_CUSTOM_STATUS_PENDING);
        $this->assertEquals(false, $production->getIsCompleted());
    }
}