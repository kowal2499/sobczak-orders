<?php

namespace App\Module\WorkingSchedule\Entity;

use App\Module\WorkingSchedule\Repository\WorkingScheduleRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use App\Module\WorkingSchedule\ValueObject\ScheduleDayType;

#[ORM\Entity(repositoryClass: WorkingScheduleRepository::class)]
class WorkingSchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'date')]
    private $date;

    #[ORM\Column(type: 'string', enumType: ScheduleDayType::class)]
    private ScheduleDayType $dayType;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $description;

    /**
     * @param $date
     * @param ScheduleDayType $dayType
     * @param string|null $description
     */
    public function __construct($date, ScheduleDayType $dayType, ?string $description = null)
    {
        $this->date = $date;
        $this->dayType = $dayType;
        $this->description = $description;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDayType(): ScheduleDayType
    {
        return $this->dayType;
    }

    public function setDayType(ScheduleDayType $dayType): self
    {
        $this->dayType = $dayType;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->format('Y-m-d'),
            'dayType' => $this->dayType->value,
            'description' => $this->description,
        ];
    }
}
