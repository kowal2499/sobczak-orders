<?php

namespace App\Tests\Unit\Service\Production;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Exceptions\Production\StatusNotMatchWithTaskTypeException;
use App\Service\DateTimeHelper;
use App\Service\Production\TaskStatusService;
use PHPUnit\Framework\TestCase;

class TasksFlagsServiceTest extends TestCase
{
    /** @var TaskStatusService */
    private $serviceUnderTest;

    /** @var DateTimeHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $dateTimeHelperMock;

    protected function setUp(): void
    {
        $this->dateTimeHelperMock = $this->createMock(DateTimeHelper::class);
        $this->serviceUnderTest = new TaskStatusService($this->dateTimeHelperMock);
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

    public function testShouldSetStatus()
    {
        // Given
        $production = new Production();
        $production->setDepartmentSlug(TaskTypes::TYPE_DEFAULT_SLUG_GLUING);
        // When & Then
        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_PENDING);
        $this->assertEquals(TaskTypes::TYPE_DEFAULT_STATUS_PENDING, $production->getStatus());
    }

    public function testShouldConfirmCompletionWhenStatusIsSetAsCompletedAndTaskTypeIsDefault()
    {
        // Given
        $production = new Production();
        $production->setDepartmentSlug(TaskTypes::TYPE_DEFAULT_SLUG_GLUING);
        // When & Then
        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED);
        $this->assertTrue($production->getIsCompleted());

        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE);
        $this->assertFalse($production->getIsCompleted());

        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_PENDING);
        $this->assertFalse($production->getIsCompleted());

        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_AWAITS);
        $this->assertFalse($production->getIsCompleted());

        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_STARTED);
        $this->assertFalse($production->getIsCompleted());
    }

    public function testShouldConfirmCompletionWhenStatusIsSetAsCompletedAndTaskTypeIsCustom()
    {
        // Given
        $production = new Production();
        $production->setDepartmentSlug(TaskTypes::TYPE_CUSTOM_SLUG);
        // When & Then
        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_CUSTOM_STATUS_COMPLETED);
        $this->assertTrue($production->getIsCompleted());

        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_CUSTOM_STATUS_AWAITS);
        $this->assertFalse($production->getIsCompleted());

        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_CUSTOM_STATUS_PENDING);
        $this->assertFalse($production->getIsCompleted());
    }

    public function testShouldSetIsStartDelayedFlagToTrueWhenStartDateHasPassed()
    {
        // Given
        $production = new Production();
        $production->setDepartmentSlug(TaskTypes::TYPE_DEFAULT_SLUG_GLUING);
        $production->setDateStart(new \DateTime('2021-05-10'));

        $this->dateTimeHelperMock
            ->expects($this->once())
            ->method('today')
            ->willReturn(new \DateTime('2021-05-15'));

        // When
        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_STARTED);
        // Then
        $this->assertTrue($production->getIsStartDelayed());
    }

    public function testShouldSetIsStartDelayedFlagToFalseWhenStartDateHasNotPassed()
    {
        // Given
        $production = new Production();
        $production->setDepartmentSlug(TaskTypes::TYPE_DEFAULT_SLUG_GLUING);
        $production->setDateStart(new \DateTime('2021-05-10'));

        $this->dateTimeHelperMock
            ->expects($this->once())
            ->method('today')
            ->willReturn(new \DateTime('2021-05-05'));

        // When
        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_STARTED);
        // Then
        $this->assertFalse($production->getIsStartDelayed());
    }

    public function testDoNotSetIsStartDelayedFlagIfDateStartIsNull()
    {
        // Given
        $production = new Production();
        $production->setDepartmentSlug(TaskTypes::TYPE_DEFAULT_SLUG_GLUING);

        // When
        $this->serviceUnderTest->setStatus($production, TaskTypes::TYPE_DEFAULT_STATUS_STARTED);

        // Then
        $this->assertNull($production->getIsStartDelayed());
    }

    /**
     * @dataProvider resetFlagConditions
     */
    public function testShouldSetIsStartDelayedToFalseWhenTaskIsCompletedOrNotApplicable(
        $taskSlug, $newStatus
    )
    {
        // Given
        $production = new Production();
        $production->setDepartmentSlug($taskSlug);
        $production->setDateStart(new \DateTime());
        $production->setIsStartDelayed(true);
        // When
        $this->serviceUnderTest->setStatus($production, $newStatus);
        // Then
        $this->assertFalse($production->getIsStartDelayed());
    }

    /**
     * @dataProvider keepFlagConditions
     */
    public function testShouldNotChangeFlagIfTaskStatusIsDifferentThanStarting(
        $taskSlug, $newStatus
    )
    {
        // Given
        $production = new Production();
        $production->setDepartmentSlug($taskSlug);
        $production->setDateStart(new \DateTime('2021-05-15'));
        $production->setIsStartDelayed(true);

        $this->dateTimeHelperMock
            ->method('today')
            ->willReturn(new \DateTime('2021-05-10'));

        // When
        $this->serviceUnderTest->setStatus($production, $newStatus);

        // Then
        $this->assertTrue($production->getIsStartDelayed());
    }

    public function resetFlagConditions(): array
    {
        return [
            [TaskTypes::TYPE_DEFAULT_SLUG_GLUING, TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED],
            [TaskTypes::TYPE_DEFAULT_SLUG_GLUING, TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE],
            [TaskTypes::TYPE_CUSTOM_SLUG, TaskTypes::TYPE_CUSTOM_STATUS_COMPLETED],
        ];
    }

    public function keepFlagConditions(): array
    {
        return [
            [TaskTypes::TYPE_DEFAULT_SLUG_GLUING, TaskTypes::TYPE_DEFAULT_STATUS_AWAITS],
            [TaskTypes::TYPE_DEFAULT_SLUG_GLUING, TaskTypes::TYPE_DEFAULT_STATUS_PENDING],
        ];
    }
}