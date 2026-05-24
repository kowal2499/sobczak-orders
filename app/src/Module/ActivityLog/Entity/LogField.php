<?php

namespace App\Module\ActivityLog\Entity;

use App\Module\ActivityLog\Repository\LogFieldRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogFieldRepository::class)]
#[ORM\Table(name: 'activity_log_field')]
#[ORM\Index(name: 'idx_activity_log_field_log_id', columns: ['activity_log_id'])]
#[ORM\Index(name: 'idx_activity_log_field_name', columns: ['name'])]
#[ORM\UniqueConstraint(name: 'uq_activity_log_field_log_name', columns: ['activity_log_id', 'name'])]
class LogField
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ActivityLog::class, inversedBy: 'logFields')]
    #[ORM\JoinColumn(name: 'activity_log_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ActivityLog $log;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $value;

    public function __construct(ActivityLog $log, string $name, string $value)
    {
        $this->log = $log;
        $this->name = $name;
        $this->value = $value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLog(): ActivityLog
    {
        return $this->log;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
