<?php

namespace App\Module\Production\Service\ProductionDateStrategy;

interface ProductionDateStrategyInterface
{
    public function getName(): string;

    /**
     * @return array<string, array{dateStart: \DateTimeImmutable, dateEnd: \DateTimeImmutable}>
     *         Map indexed by department slug (dpt01..dpt06)
     */
    public function calculate(\DateTimeInterface $confirmedDate): array;

    /**
     * Self-describing definition consumable by the frontend (StartProductionAction).
     * Format follows the existing JS scheduler config.
     *
     * @return array<string, mixed>
     */
    public function getDefinition(): array;
}
