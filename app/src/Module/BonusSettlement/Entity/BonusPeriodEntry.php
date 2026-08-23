<?php

namespace App\Module\BonusSettlement\Entity;

use App\Entity\User;
use App\Module\BonusSettlement\Repository\BonusPeriodEntryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Jeden wiersz rozliczenia: premia jednego pracownika z tytułu jednego działu. Pracownik
 * z rolami w kilku działach ma osobny wiersz dla każdego z nich, z pełną sumą działu.
 *
 * Nazwa pracownika i działu są zapisane jako tekst, żeby zamknięty miesiąc dało się wyświetlić
 * po zmianie nazwy albo usunięciu użytkownika.
 */
#[ORM\Entity(repositoryClass: BonusPeriodEntryRepository::class)]
#[ORM\Table(name: 'bonus_period_entry')]
#[ORM\UniqueConstraint(
    name: 'unique_bonus_entry_period_user_department',
    columns: ['period_id', 'user_id', 'department_slug']
)]
class BonusPeriodEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BonusPeriod::class, inversedBy: 'entries')]
    #[ORM\JoinColumn(name: 'period_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private BonusPeriod $period;

    /**
     * Celowo bez onDelete: usunięcie użytkownika mającego rozliczone premie ma się nie udać.
     * Aplikacja i tak nie kasuje użytkowników (dezaktywacja to User::$active), a NULL rozbroiłby
     * unikat poniżej i dopasowanie korekt przy przeliczeniu.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\Column(type: 'string', length: 255)]
    private string $userLabel;

    #[ORM\Column(type: 'string', length: 10)]
    private string $departmentSlug;

    #[ORM\Column(type: 'string', length: 255)]
    private string $departmentLabel;

    #[ORM\Column(type: 'float')]
    private float $factorsCalculated;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $factorsAdjusted = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    /**
     * Czas zapisu korekty, informacyjnie. Przeliczenie okresu celowo go nie odświeża.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $adjustedAt = null;

    /**
     * Wsad, przy którym zapadła decyzja o korekcie. Rozjazd z aktualnym factorsCalculated
     * znaczy, że korekta wisi przy liczbie, której już nie ma. Porównanie wartości zamiast
     * dat, bo po przeliczeniu daty rozjeżdżają się we wszystkich wierszach naraz, także tych,
     * których wsad się nie ruszył.
     */
    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $adjustedAgainst = null;

    public function __construct(
        BonusPeriod $period,
        User $user,
        string $userLabel,
        string $departmentSlug,
        string $departmentLabel,
        float $factorsCalculated,
    ) {
        $this->period = $period;
        $this->user = $user;
        $this->userLabel = $userLabel;
        $this->departmentSlug = $departmentSlug;
        $this->departmentLabel = $departmentLabel;
        $this->factorsCalculated = $factorsCalculated;
        $period->addEntry($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeriod(): BonusPeriod
    {
        return $this->period;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUserLabel(): string
    {
        return $this->userLabel;
    }

    public function setUserLabel(string $userLabel): void
    {
        $this->userLabel = $userLabel;
    }

    public function getDepartmentSlug(): string
    {
        return $this->departmentSlug;
    }

    public function getDepartmentLabel(): string
    {
        return $this->departmentLabel;
    }

    public function setDepartmentLabel(string $departmentLabel): void
    {
        $this->departmentLabel = $departmentLabel;
    }

    public function getFactorsCalculated(): float
    {
        return $this->factorsCalculated;
    }

    public function setFactorsCalculated(float $factorsCalculated): void
    {
        $this->factorsCalculated = $factorsCalculated;
    }

    public function getFactorsAdjusted(): ?float
    {
        return $this->factorsAdjusted;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getAdjustedAt(): ?\DateTimeImmutable
    {
        return $this->adjustedAt;
    }

    public function getAdjustedAgainst(): ?float
    {
        return $this->adjustedAgainst;
    }

    /**
     * Korekta, jej czas i wsad, przy którym zapadła, zawsze idą razem - stąd jedna metoda
     * zamiast czterech setterów.
     */
    public function adjust(float $factorsAdjusted, ?string $note, ?\DateTimeImmutable $adjustedAt = null): void
    {
        $this->factorsAdjusted = $factorsAdjusted;
        $this->note = $note;
        $this->adjustedAt = $adjustedAt ?? new \DateTimeImmutable();
        $this->adjustedAgainst = $this->factorsCalculated;
    }

    public function clearAdjustment(): void
    {
        $this->factorsAdjusted = null;
        $this->note = null;
        $this->adjustedAt = null;
        $this->adjustedAgainst = null;
    }

    /**
     * Czy korekta odnosi się do wsadu, którego już nie ma. Porównanie z tolerancją, bo wsad
     * jest sumą floatów i po przeliczeniu może się różnić o końcówkę bez znaczenia dla premii
     * (wartości pokazujemy z dokładnością do dwóch miejsc).
     */
    public function isAdjustmentStale(): bool
    {
        if (null === $this->adjustedAgainst) {
            return false;
        }

        return abs($this->adjustedAgainst - $this->factorsCalculated) > 0.005;
    }

    /**
     * Wartość obowiązująca. Korekta 0 jest pełnoprawnym odebraniem premii, więc rozstrzyga
     * null, a nie falsy - stąd ?? zamiast ?:.
     */
    public function getEffectiveFactors(): float
    {
        return $this->factorsAdjusted ?? $this->factorsCalculated;
    }

    public function isAdjusted(): bool
    {
        return null !== $this->factorsAdjusted;
    }
}
