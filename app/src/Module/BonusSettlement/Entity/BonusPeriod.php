<?php

namespace App\Module\BonusSettlement\Entity;

use App\Entity\User;
use App\Module\BonusSettlement\Repository\BonusPeriodRepository;
use App\Module\BonusSettlement\ValueObject\BonusPeriodStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Miesiąc rozliczeniowy premii. Zakładany ręcznie - rozliczeniu podlegają tylko te miesiące,
 * które ktoś świadomie otworzył.
 *
 * Tolerancja użyta przy ostatnim przeliczeniu jest zapisana na okresie, żeby zamknięty miesiąc
 * dało się odtworzyć po zmianie domyślnych widełek terminowości.
 */
#[ORM\Entity(repositoryClass: BonusPeriodRepository::class)]
#[ORM\Table(name: 'bonus_period')]
#[ORM\UniqueConstraint(name: 'unique_bonus_period_year_month', columns: ['year', 'month'])]
class BonusPeriod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'smallint')]
    private int $year;

    #[ORM\Column(type: 'smallint')]
    private int $month;

    #[ORM\Column(type: 'string', length: 16, enumType: BonusPeriodStatus::class)]
    private BonusPeriodStatus $status = BonusPeriodStatus::OPEN;

    #[ORM\Column(type: 'smallint')]
    private int $toleranceDays;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $calculatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'closed_by', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $closedBy = null;

    /** @var Collection<int, BonusPeriodEntry> */
    #[ORM\OneToMany(mappedBy: 'period', targetEntity: BonusPeriodEntry::class, cascade: ['persist'])]
    private Collection $entries;

    public function __construct(int $year, int $month, int $toleranceDays)
    {
        $this->year = $year;
        $this->month = $month;
        $this->toleranceDays = $toleranceDays;
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getMonth(): int
    {
        return $this->month;
    }

    public function getStatus(): BonusPeriodStatus
    {
        return $this->status;
    }

    public function isOpen(): bool
    {
        return BonusPeriodStatus::OPEN === $this->status;
    }

    public function isClosed(): bool
    {
        return BonusPeriodStatus::CLOSED === $this->status;
    }

    public function getToleranceDays(): int
    {
        return $this->toleranceDays;
    }

    public function setToleranceDays(int $toleranceDays): void
    {
        $this->toleranceDays = $toleranceDays;
    }

    public function getCalculatedAt(): ?\DateTimeImmutable
    {
        return $this->calculatedAt;
    }

    public function setCalculatedAt(?\DateTimeImmutable $calculatedAt): void
    {
        $this->calculatedAt = $calculatedAt;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function getClosedBy(): ?User
    {
        return $this->closedBy;
    }

    public function close(User $closedBy, ?\DateTimeImmutable $closedAt = null): void
    {
        $this->status = BonusPeriodStatus::CLOSED;
        $this->closedAt = $closedAt ?? new \DateTimeImmutable();
        $this->closedBy = $closedBy;
    }

    public function reopen(): void
    {
        $this->status = BonusPeriodStatus::OPEN;
        $this->closedAt = null;
        $this->closedBy = null;
    }

    /**
     * @return Collection<int, BonusPeriodEntry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(BonusPeriodEntry $entry): void
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
        }
    }

    public function removeEntry(BonusPeriodEntry $entry): void
    {
        $this->entries->removeElement($entry);
    }

    /**
     * Pierwszy dzień miesiąca, godzina 00:00:00. Miernik rozlicza po completedAt, więc granice
     * zakresu są dobowe.
     */
    public function getRangeStart(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $this->year, $this->month));
    }

    /**
     * Ostatni dzień miesiąca, godzina 23:59:59.
     */
    public function getRangeEnd(): \DateTimeImmutable
    {
        return $this->getRangeStart()->modify('last day of this month')->setTime(23, 59, 59);
    }
}
