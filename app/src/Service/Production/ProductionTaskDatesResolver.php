<?php

namespace App\Service\Production;

use App\Entity\Production;
use DateTime;
use DateTimeInterface;

class ProductionTaskDatesResolver
{
    /**
     * @param Production $production
     * @param DateTime $deadline
     * @return DateTimeInterface|null
     */
    public function resolveDateFrom(Production $production, DateTimeInterface $deadline): ?DateTimeInterface
    {
        if ($production->getDepartmentSlug() !== 'dpt01')
        {
            return null;
        }
        return (clone $deadline)->modify('-7 days')->setTime(7, 0);
    }

    /**
     * @param Production $production
     * @param DateTimeInterface $deadline
     * @return DateTimeInterface
     */
    public function resolveDateTo(
        Production $production, DateTimeInterface $deadline
    ): DateTimeInterface
    {
        return $deadline;
    }
}