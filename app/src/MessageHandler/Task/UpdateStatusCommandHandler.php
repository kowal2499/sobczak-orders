<?php

namespace App\MessageHandler\Task;

use App\Entity\StatusLog;
use App\Message\AgreementLine\UpdateProductionCompletionDate;
use App\Message\Task\UpdateStatusCommand;
use App\Module\Agreement\Event\AgreementLineWasUpdatedEvent;
use App\Repository\ProductionRepository;
use App\Service\Production\TaskStatusService;
use App\System\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Security;

class UpdateStatusCommandHandler implements MessageHandlerInterface
{
    private $taskRepository;
    private $em;
    private $security;
    private $statusService;
    private $messageBus;
    private EventBus $eventBus;

    public function __construct(
        ProductionRepository $taskRepository,
        EntityManagerInterface $em,
        Security $security,
        TaskStatusService $statusService,
        MessageBusInterface $messageBus,
        EventBus $eventBus,
    )
    {
        $this->taskRepository = $taskRepository;
        $this->em = $em;
        $this->security = $security;
        $this->statusService = $statusService;
        $this->messageBus = $messageBus;
        $this->eventBus = $eventBus;
    }

    public function __invoke(UpdateStatusCommand $command)
    {
        $task = $this->taskRepository->findOneBy(['id' => $command->getTaskId()]);

        // quit if status is set but has not changed
        if (null !== $task->getStatus() && ((int)$task->getStatus() === $command->getNewStatus())) {
            return;
        }

        $this->statusService->setStatus($task, $command->getNewStatus());

        $statusLog = new StatusLog();
        $statusLog
            ->setProduction($task)
            ->setCurrentStatus($command->getNewStatus())
            ->setCreatedAt(new \DateTime())
            ->setUser($this->security->getUser());

        $this->em->persist($task);
        $this->em->persist($statusLog);
        $this->em->flush();

        $this->messageBus->dispatch(new UpdateProductionCompletionDate($task->getAgreementLine()->getId()));
        $this->eventBus->dispatch(new AgreementLineWasUpdatedEvent($task->getAgreementLine()->getId()) );
    }
}