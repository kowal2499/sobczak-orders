<?php

namespace App\Module\AgreementLine\Entity;

use App\Module\Production\Factor\DTO\AssembledFactorDTO;
use DateTime;
use DateTimeInterface;

class ProductionRM
{
    private ?int $id;
    private string $departmentSlug;
    private ?DateTimeInterface $dateStart;
    private ?DateTimeInterface $dateEnd;
    private ?string $status;
    private ?bool $isStartDelayed;
    private ?bool $isCompleted;
    private ?DateTimeInterface $completedAt;
    private ?AssembledFactorDTO $factorRatio;
    private ?AssembledFactorDTO $factorBonus;

    public function __construct(
        string $departmentSlug,
        ?int $id = null,
        ?DateTimeInterface $dateStart = null,
        ?DateTimeInterface $dateEnd = null,
        ?string $status = null,
        ?bool $isStartDelayed = null,
        ?bool $isCompleted = null,
        ?DateTimeInterface $completedAt = null,
        ?AssembledFactorDTO $factorRatio = null,
        ?AssembledFactorDTO $factorBonus = null,
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepartmentSlug(): string
    {
        return $this->departmentSlug;
    }

    public function getDateStart(): ?DateTimeInterface
    {
        return $this->dateStart;
    }

    public function getDateEnd(): ?DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function isStartDelayed(): ?bool
    {
        return $this->isStartDelayed;
    }

    public function isCompleted(): ?bool
    {
        return $this->isCompleted;
    }

    public function getCompletedAt(): ?DateTimeInterface
    {
        return $this->completedAt;
    }

    public function getFactorRatio(): ?AssembledFactorDTO
    {
        return $this->factorRatio;
    }

    public function getFactorBonus(): ?AssembledFactorDTO
    {
        return $this->factorBonus;
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

    public function setFactorRatio(?AssembledFactorDTO $factorRatio): void
    {
        $this->factorRatio = $factorRatio;
    }

    public function setFactorBonus(?AssembledFactorDTO $factorBonus): void
    {
        $this->factorBonus = $factorBonus;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'departmentSlug' => $this->departmentSlug,
            'dateStart' => $this->dateStart?->format('Y-m-d H:i:s'),
            'dateEnd' => $this->dateEnd?->format('Y-m-d H:i:s'),
            'status' => $this->status,
            'isStartDelayed' => $this->isStartDelayed,
            'isCompleted' => $this->isCompleted,
            'completedAt' => $this->completedAt?->format('Y-m-d H:i:s'),
            'factorRatio' => $this->factorRatio?->toArray() ?? null,
            'factorBonus' => $this->factorBonus?->toArray() ?? null,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            departmentSlug: $data['departmentSlug'],
            id: $data['id'] ?? null,
            dateStart: isset($data['dateStart']) ? new DateTime($data['dateStart']) : null,
            dateEnd: isset($data['dateEnd']) ? new DateTime($data['dateEnd']) : null,
            status: $data['status'] ?? null,
            isStartDelayed: $data['isStartDelayed'] ?? null,
            isCompleted: $data['isCompleted'] ?? null,
            completedAt: isset($data['completedAt']) ? new DateTime($data['completedAt']) : null,
            factorRatio: $data['factorRatio'] ?? null,
            factorBonus: $data['factorBonus'] ?? null,
        );
    }
}