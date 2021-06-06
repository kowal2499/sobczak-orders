<?php

namespace App\MessageHandler\Task;

use App\Entity\StatusLog;
use App\Message\Task\UpdateStatusCommand;
use App\Repository\ProductionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Security\Core\Security;

class UpdateStatusCommandHandler implements MessageHandlerInterface
{
    private $taskRepository;
    private $em;
    private $security;

    public function __construct(
        ProductionRepository $taskRepository,
        EntityManagerInterface $em,
        Security $security
    )
    {
        $this->taskRepository = $taskRepository;
        $this->em = $em;
        $this->security = $security;
    }

    public function __invoke(UpdateStatusCommand $command)
    {
        $task = $this->taskRepository->findOneBy(['id' => $command->getTaskId()]);
        $task->setStatus($command->getNewStatus());

        $statusLog = new StatusLog();
        $statusLog
            ->setProduction($task)
            ->setCurrentStatus($command->getNewStatus())
            ->setCreatedAt(new \DateTime())
            ->setUser($this->security->getUser())
        ;

        $this->em->persist($task);
        $this->em->persist($statusLog);

        $this->em->flush();
    }
}