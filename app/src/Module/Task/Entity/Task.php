<?php

namespace App\Module\Task\Entity;

use App\Entity\AgreementLine;
use App\Entity\User;
use App\Module\Task\Repository\TaskRepository;
use App\Module\Task\ValueObject\TaskStatusEnum;
use App\Module\Task\ValueObject\TaskTypeEnum;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
#[ORM\Index(name: 'idx_task_agreement_line_id', columns: ['agreement_line_id'])]
#[ORM\Index(name: 'idx_task_owner_id', columns: ['owner_id'])]
#[ORM\Index(name: 'idx_task_status', columns: ['status'])]
#[ORM\Index(name: 'idx_task_is_deleted', columns: ['is_deleted'])]
#[ORM\Index(name: 'idx_task_type', columns: ['type'])]
class Task extends BaseTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'smallint')]
    private int $status;

    #[ORM\Column(type: 'string', length: 64)]
    private string $type;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted = false;


    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: true)]
    private ?User $owner = null;

    #[ORM\ManyToOne(targetEntity: AgreementLine::class)]
    #[ORM\JoinColumn(name: 'agreement_line_id', referencedColumnName: 'id', nullable: false)]
    private AgreementLine $agreementLine;

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusEnum(): TaskStatusEnum
    {
        return TaskStatusEnum::from($this->status);
    }

    public function setStatusEnum(TaskStatusEnum $status): self
    {
        $this->status = $status->value;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getTypeEnum(): TaskTypeEnum
    {
        return TaskTypeEnum::from($this->type);
    }

    public function setTypeEnum(TaskTypeEnum $type): self
    {
        $this->type = $type->value;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getAgreementLine(): AgreementLine
    {
        return $this->agreementLine;
    }

    public function setAgreementLine(AgreementLine $agreementLine): self
    {
        $this->agreementLine = $agreementLine;
        return $this;
    }
}
