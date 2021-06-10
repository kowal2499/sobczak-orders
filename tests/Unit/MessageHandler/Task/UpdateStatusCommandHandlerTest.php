<?php

namespace App\Tests\Unit\MessageHandler\Task;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Message\Task\UpdateStatusCommand;
use App\MessageHandler\Task\UpdateStatusCommandHandler;
use App\Repository\ProductionRepository;
use App\Service\Production\TaskStatusService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;

class UpdateStatusCommandHandlerTest extends TestCase
{
    /** @var ProductionRepository|MockObject */
    private $taskRepository;

    /** @var UpdateStatusCommandHandler */
    private $handerUnderTest;
    /** @var TaskStatusService|MockObject */
    private $statusService;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(ProductionRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->statusService = $this->createMock(TaskStatusService::class);

        $this->handerUnderTest = new UpdateStatusCommandHandler(
            $this->taskRepository,
            $this->em,
            $this->security,
            $this->statusService
        );
    }

    public function testShouldNotUpdateStatusWhenStatusHasNotChanged()
    {
        // Given
        $task = new Production();
        $task->setStatus(TaskTypes::TYPE_DEFAULT_STATUS_STARTED);
        $handler = $this->handerUnderTest;
        $command = new UpdateStatusCommand(12, TaskTypes::TYPE_DEFAULT_STATUS_STARTED);

        $this->taskRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 12])
            ->willReturn($task);

        $this->statusService->expects($this->never())->method('setStatus');

        // When && Then
        $handler($command);
    }

    public function testShouldUpdateStatusWhenStatusIsNull()
    {
        // Given
        $task = new Production();
        $handler = $this->handerUnderTest;
        $command = new UpdateStatusCommand(12, TaskTypes::TYPE_DEFAULT_STATUS_STARTED);

        $this->taskRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 12])
            ->willReturn($task);

        $this->statusService->expects($this->once())->method('setStatus');
        // When
        $handler($command);
    }
}