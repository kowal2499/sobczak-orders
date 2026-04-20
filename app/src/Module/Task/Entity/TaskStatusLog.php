<?php

namespace App\Module\Task\Entity;

use App\Entity\User;
use App\Module\Task\Repository\TaskStatusLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskStatusLogRepository::class)]
#[ORM\Table(name: 'task_status_log')]
#[ORM\Index(name: 'idx_task_status_log_task_id', columns: ['task_id'])]
class TaskStatusLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'statusLogs')]
    #[ORM\JoinColumn(name: 'task_id', nullable: false, onDelete: 'CASCADE')]
    private Task $task;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $previousStatus;

    #[ORM\Column(type: 'smallint')]
    private int $currentStatus;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    public function __construct(Task $task, int $currentStatus, ?int $previousStatus, ?User $user)
    {
        $this->task = $task;
        $this->currentStatus = $currentStatus;
        $this->previousStatus = $previousStatus;
        $this->user = $user;
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getPreviousStatus(): ?int
    {
        return $this->previousStatus;
    }

    public function getCurrentStatus(): int
    {
        return $this->currentStatus;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
