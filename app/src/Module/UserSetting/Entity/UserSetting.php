<?php

namespace App\Module\UserSetting\Entity;

use App\Entity\User;
use App\Module\UserSetting\Repository\UserSettingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSettingRepository::class)]
#[ORM\Table(name: 'user_setting')]
#[ORM\UniqueConstraint(name: 'unique_user_setting_context', columns: ['user_id', 'context'])]
class UserSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 190)]
    private string $context;

    #[ORM\Column(type: 'json')]
    private array $data = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $user, string $context, array $data)
    {
        $this->user = $user;
        $this->context = $context;
        $this->data = $data;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
