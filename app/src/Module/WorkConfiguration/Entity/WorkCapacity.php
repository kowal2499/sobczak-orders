<?php

namespace App\Module\WorkConfiguration\Entity;

use App\Module\WorkConfiguration\Repository\WorkCapacityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkCapacityRepository::class)]
#[ORM\Table(name: "work_capacity")]
#[ORM\Index(name: "idx_date_from", columns: ["date_from"])]
class WorkCapacity
{
    public const DEFAULT_CAPACITY = 1.5238;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $dateFrom;

    #[ORM\Column(type: 'float')]
    private float $capacity;

    /**
     * @param \DateTimeInterface $dateFrom
     * @param float $capacity
     */
    public function __construct(\DateTimeInterface $dateFrom, float $capacity)
    {
        $this->dateFrom = $dateFrom;
        $this->capacity = $capacity;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDateFrom(): \DateTimeInterface
    {
        return $this->dateFrom;
    }

    public function getCapacity(): float
    {
        return $this->capacity;
    }

    public function setDateFrom(\DateTimeInterface $dateFrom): void
    {
        $this->dateFrom = $dateFrom;
    }

    public function setCapacity(float $capacity): void
    {
        $this->capacity = $capacity;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'dateFrom' => $this->getDateFrom()->format('Y-m-d'),
            'capacity' => $this->getCapacity(),
        ];
    }
}
