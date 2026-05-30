<?php

namespace App\Module\ActivityLog\Entity;

use App\Entity\User;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Module\ActivityLog\ValueObject\LogPriority;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
#[ORM\Table(name: 'activity_log')]
#[ORM\Index(name: 'idx_activity_log_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_activity_log_type', columns: ['type'])]
#[ORM\Index(name: 'idx_activity_log_created_at', columns: ['created_at'])]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(type: 'text')]
    private string $content;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'content_params', type: 'json', nullable: true)]
    private ?array $contentParams = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user;

    #[ORM\Column(type: 'string', length: 10, enumType: LogLevel::class)]
    private LogLevel $level;

    #[ORM\Column(type: 'string', length: 32, enumType: LogPriority::class)]
    private LogPriority $priority;

    /**
     * @Gedmo\Timestampable(on="create")
     */
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     */
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    /**
     * @var Collection<int, LogField>
     */
    #[ORM\OneToMany(mappedBy: 'log', targetEntity: LogField::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $logFields;

    /**
     * @param array<string, mixed>|null $contentParams Presentation-only parameters for interpolation of the
     *                                                 translation key held in $content. Not queryable — use LogField
     *                                                 for filterable/groupable data.
     */
    public function __construct(
        string $type,
        string $content,
        ?User $user,
        LogLevel $level = LogLevel::INFO,
        LogPriority $priority = LogPriority::normal,
        ?array $contentParams = null,
    ) {
        $this->type = $type;
        $this->content = $content;
        $this->user = $user;
        $this->level = $level;
        $this->priority = $priority;
        $this->contentParams = $contentParams;
        $this->logFields = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getContentParams(): ?array
    {
        return $this->contentParams;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getLevel(): LogLevel
    {
        return $this->level;
    }

    public function getPriority(): LogPriority
    {
        return $this->priority;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, LogField>
     */
    public function getLogFields(): Collection
    {
        return $this->logFields;
    }

    /**
     * Idempotent by name — first value wins, subsequent calls with the same name are no-ops.
     */
    public function addLogField(string $name, string $value): self
    {
        foreach ($this->logFields as $existing) {
            if ($existing->getName() === $name) {
                return $this;
            }
        }

        $this->logFields->add(new LogField($this, $name, $value));
        return $this;
    }
}
