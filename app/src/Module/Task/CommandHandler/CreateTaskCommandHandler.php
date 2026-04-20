<?php

namespace App\Module\Task\CommandHandler;

use App\Entity\AgreementLine;
use App\Entity\User;
use App\Module\Task\Command\CreateTaskCommand;
use App\Module\Task\Entity\Task;
use App\Module\Task\Entity\TaskStatusLog;
use App\Module\Task\Event\TaskWasCreatedEvent;
use App\Module\Task\Repository\TaskRepository;
use App\Module\Task\ValueObject\TaskStatusEnum;
use App\Module\Task\ValueObject\TaskTypeEnum;
use App\Repository\AgreementLineRepository;
use App\Repository\UserRepository;
use App\System\EventBus;
use Doctrine\ORM\EntityManagerInterface;

class CreateTaskCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TaskRepository $taskRepository,
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly UserRepository $userRepository,
        private readonly EventBus $eventBus,
    ) {
    }

    public function __invoke(CreateTaskCommand $command): void
    {
        $this->em->beginTransaction();

        try {
            $agreementLine = $this->getAgreementLine($command->agreementLineId);
            $owner = $this->getOwner($command->ownerId);

            $this->validateDates($command->dateStart, $command->dateEnd);

            $task = $this->createTask($command, $agreementLine, $owner);

            $creator = $command->createdByUserId ? $this->userRepository->find($command->createdByUserId) : null;
            $task->addStatusLog(new TaskStatusLog($task, $task->getStatus(), null, $creator));

            $this->taskRepository->save($task, true);

            $this->eventBus->dispatch(new TaskWasCreatedEvent(
                $task->getId(),
                $agreementLine->getId()
            ));

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    private function getAgreementLine(int $agreementLineId): AgreementLine
    {
        $agreementLine = $this->agreementLineRepository->find($agreementLineId);

        if (!$agreementLine) {
            throw new \InvalidArgumentException('AgreementLine not found');
        }

        return $agreementLine;
    }

    private function getOwner(?int $ownerId): ?User
    {
        if ($ownerId === null) {
            return null;
        }

        $owner = $this->userRepository->find($ownerId);

        if (!$owner) {
            throw new \InvalidArgumentException('Owner not found');
        }

        return $owner;
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

    private function createTask(
        CreateTaskCommand $command,
        AgreementLine $agreementLine,
        ?User $owner
    ): Task {
        $task = new Task();
        $task->setAgreementLine($agreementLine);
        $task->setDateStart($command->dateStart ? new \DateTime($command->dateStart) : null);
        $task->setDateEnd($command->dateEnd ? new \DateTime($command->dateEnd) : null);
        $task->setStatusEnum(TaskStatusEnum::from($command->status));
        $task->setTypeEnum(TaskTypeEnum::from($command->type));
        $task->setTitle($command->title);
        $task->setDescription($command->description);
        $task->setOwner($owner);
        $task->setIsDeleted(false);

        return $task;
    }
}
