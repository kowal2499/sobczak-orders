<?php

namespace App\Module\Task\CommandHandler;

use App\Module\Task\Command\UpdateTaskCommand;
use App\Module\Task\Entity\Task;
use App\Module\Task\Event\TaskWasUpdatedEvent;
use App\Module\Task\Repository\TaskRepository;
use App\Module\Task\ValueObject\TaskStatusEnum;
use App\System\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UpdateTaskCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TaskRepository $taskRepository,
        private readonly EventBus $eventBus,
    ) {
    }

    public function __invoke(UpdateTaskCommand $command): void
    {
        $this->em->beginTransaction();

        try {
            $task = $this->getTask($command->taskId);

            $this->validateOwnership($task, $command->userId);
            $this->validateDates($command->dateStart, $command->dateEnd);

            $this->updateTask($task, $command);

            $this->taskRepository->save($task);

            $this->eventBus->dispatch(new TaskWasUpdatedEvent(
                $task->getId(),
                $task->getAgreementLine()->getId()
            ));

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    private function getTask(int $taskId): Task
    {
        $task = $this->taskRepository->find($taskId);

        if (!$task) {
            throw new \InvalidArgumentException('Task not found');
        }

        return $task;
    }

    private function validateOwnership(Task $task, int $userId): void
    {
        $owner = $task->getOwner();

        if ($owner !== null && $owner->getId() !== $userId) {
            throw new AccessDeniedException('Only the task owner can update this task');
        }
    }

    private function validateDates(?string $dateStart, ?string $dateEnd): void
    {
        // Walidacja tylko gdy obie daty są podane
        if ($dateStart === null || $dateEnd === null) {
            return;
        }

        $start = new \DateTimeImmutable($dateStart);
        $end = new \DateTimeImmutable($dateEnd);

        if ($end < $start) {
            throw new \InvalidArgumentException('Date end must be greater than or equal to date start');
        }
    }

    private function updateTask(Task $task, UpdateTaskCommand $command): void
    {
        $task->setDateStart($command->dateStart ? new \DateTime($command->dateStart) : null);
        $task->setDateEnd($command->dateEnd ? new \DateTime($command->dateEnd) : null);
        $task->setStatusEnum(TaskStatusEnum::from($command->status));
        $task->setTitle($command->title);
        $task->setDescription($command->description);
    }
}
