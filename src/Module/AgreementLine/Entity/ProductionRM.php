<?php

namespace App\Module\AgreementLine\Entity;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class ProductionRM
{
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $departmentSlug;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dateStart;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dateEnd;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $status;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isStartDelayed;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $isCompleted;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $completedAt;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $factorRatio = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $factorBonus = null;

    /**
     * @param string $departmentSlug
     * @param int|null $id
     * @param DateTimeInterface|null $dateStart
     * @param DateTimeInterface|null $dateEnd
     * @param string|null $status
     * @param bool|null $isStartDelayed
     * @param bool|null $isCompleted
     * @param DateTimeInterface|null $completedAt
     * @param float|null $factorRatio
     * @param float|null $factorBonus
     */
    public function __construct(
        string $departmentSlug,
        ?int $id = null,
        ?DateTimeInterface $dateStart = null,
        ?DateTimeInterface $dateEnd = null,
        ?string $status = null,
        ?bool $isStartDelayed = null,
        ?bool $isCompleted = null,
        ?DateTimeInterface $completedAt = null,
        ?float $factorRatio = null,
        ?float $factorBonus = null,
    ) {
        $this->id = $id;
        $this->departmentSlug = $departmentSlug;
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
        $this->status = $status;
        $this->isStartDelayed = $isStartDelayed;
        $this->isCompleted = $isCompleted;
        $this->completedAt = $completedAt;
        $this->factorRatio = $factorRatio;
        $this->factorBonus = $factorBonus;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function setDepartmentSlug(string $departmentSlug): void
    {
        $this->departmentSlug = $departmentSlug;
    }

    public function setDateStart(?DateTimeInterface $dateStart): void
    {
        $this->dateStart = $dateStart;
    }

    public function setDateEnd(?DateTimeInterface $dateEnd): void
    {
        $this->dateEnd = $dateEnd;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function setIsStartDelayed(?bool $isStartDelayed): void
    {
        $this->isStartDelayed = $isStartDelayed;
    }

    public function setIsCompleted(?bool $isCompleted): void
    {
        $this->isCompleted = $isCompleted;
    }

    public function setCompletedAt(?DateTimeInterface $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    public function setFactorRatio(?float $factorRatio): void
    {
        $this->factorRatio = $factorRatio;
    }

    public function setFactorBonus(?float $factorBonus): void
    {
        $this->factorBonus = $factorBonus;
    }


}