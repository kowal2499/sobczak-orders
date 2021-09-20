<?php

namespace App\Tests\Unit\MessageHandler\Task;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Message\AgreementLine\UpdateProductionCompletionDate;
use App\Message\Task\UpdateStatusCommand;
use App\MessageHandler\Task\UpdateStatusCommandHandler;
use App\Repository\ProductionRepository;
use App\Service\Production\TaskStatusService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Security;

class UpdateStatusCommandHandlerTest extends TestCase
{
    /** @var ProductionRepository|MockObject */
    private $taskRepository;

    /** @var UpdateStatusCommandHandler */
    private $handerUnderTest;
    /** @var TaskStatusService|MockObject */
    private $statusService;
    /** @var Production|MockObject */
    private $taskUnderTest;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(ProductionRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->statusService = $this->createMock(TaskStatusService::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->messageBus->method('dispatch')->willReturnCallback(function ($command) {
            return new Envelope($command);
        });

        $this->handerUnderTest = new UpdateStatusCommandHandler(
            $this->taskRepository,
            $this->em,
            $this->security,
            $this->statusService,
            $this->messageBus
        );

        $agreementLine = $this->createMock(AgreementLine::class);
        $agreementLine->method('getId')->willReturn(123);
        $this->taskUnderTest = $this->createMock(Production::class);
        $this->taskUnderTest->method('getId')->willReturn(12);
        $this->taskUnderTest->method('getStatus')
            ->willReturn((string)TaskTypes::TYPE_DEFAULT_STATUS_AWAITS);
        $this->taskUnderTest->method('getAgreementLine')->willReturn($agreementLine);
    }

    public function testShouldNotUpdateStatusWhenStatusHasNotChanged()
    {
        // Given
        $task = $this->createMock(Production::class);
        $task->method('getId')->willReturn(12);
        $task->method('getStatus')
            ->willReturn((string)TaskTypes::TYPE_DEFAULT_STATUS_STARTED);
        $handler = $this->handerUnderTest;
        $command = new UpdateStatusCommand($task->getId(), TaskTypes::TYPE_DEFAULT_STATUS_STARTED);

        $this->taskRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $task->getId()])
            ->willReturn($task);

        $this->statusService->expects($this->never())->method('setStatus');

        // When && Then
        $handler($command);
    }

    public function testShouldUpdateStatusWhenStatusIsNull()
    {
        // Given
        $handler = $this->handerUnderTest;
        $command = new UpdateStatusCommand($this->taskUnderTest->getId(), TaskTypes::TYPE_DEFAULT_STATUS_STARTED);

        $this->taskRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => $this->taskUnderTest->getId()])
            ->willReturn($this->taskUnderTest);

        $this->statusService->expects($this->once())->method('setStatus');
        // When
        $handler($command);
    }

    public function testShouldDispatchUpdateProductionCompletionDateCommand()
    {
        // Given
        $handler = $this->handerUnderTest;
        $command = new UpdateStatusCommand($this->taskUnderTest->getId(), TaskTypes::TYPE_DEFAULT_STATUS_STARTED);

        $this->taskRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 12])
            ->willReturn($this->taskUnderTest);

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function(UpdateProductionCompletionDate $updateCompletionFlagCommand) {
                return $updateCompletionFlagCommand->getAgreementLineId() === $this->taskUnderTest->getAgreementLine()->getId();
            }))
            ->willReturnCallback(function ($command) {
                return new Envelope($command);
            })
        ;
        // When
        $handler($command);
    }
}