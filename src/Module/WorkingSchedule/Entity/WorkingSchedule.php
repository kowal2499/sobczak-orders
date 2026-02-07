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

}
