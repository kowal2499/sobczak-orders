<?php

namespace App\Module\Task\CommandHandler;

use App\Module\Task\Command\DeleteTaskCommand;
use App\Module\Task\Entity\Task;
use App\Module\Task\Event\TaskWasDeletedEvent;
use App\Module\Task\Repository\TaskRepository;
use App\System\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DeleteTaskCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TaskRepository $taskRepository,
        private readonly EventBus $eventBus,
    ) {
    }

    public function __invoke(DeleteTaskCommand $command): void
    {
        $this->em->beginTransaction();

        try {
            $task = $this->getTask($command->taskId);

            $this->validateOwnership($task, $command->userId);

            $agreementLineId = $task->getAgreementLine()->getId();

            $this->softDeleteTask($task);

            $this->taskRepository->save($task, true);

            $this->eventBus->dispatch(new TaskWasDeletedEvent(
                $task->getId(),
                $agreementLineId
            ));

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    private function getTask(int $taskId): Task
    {
        // Find with includeDeleted to allow finding already deleted tasks
        $task = $this->taskRepository->findOneBy(['id' => $taskId, 'isDeleted' => false]);

        if (!$task) {
            throw new \InvalidArgumentException('Task not found');
        }

        return $task;
    }

    private function validateOwnership(Task $task, int $userId): void
    {
        $owner = $task->getOwner();

        if ($owner !== null && $owner->getId() !== $userId) {
            throw new AccessDeniedException('Only the task owner can delete this task');
        }
    }

    private function softDeleteTask(Task $task): void
    {
        $task->setIsDeleted(true);
    }
}
