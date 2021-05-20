<?php

namespace App\Service\Production;

class ProductionTaskDatesResolver
{
    /**
     * @return \DateTimeInterface
     */
    public function resolveDateFrom(): \DateTimeInterface
    {
        return new \DateTime();
    }

    /**
     * @param string $taskSlug
     * @param \DateTimeInterface $dateFrom
     * @param \DateTimeInterface $deadlineDate
     * @return \DateTimeInterface
     */
    public function resolveDateTo(
        string $taskSlug,
        \DateTimeInterface $dateFrom,
        \DateTimeInterface $deadlineDate
    ): \DateTimeInterface
    {
        $to = clone $deadlineDate;
        switch ($taskSlug) {
            case 'dpt01':
                $to->modify('-7 days');
                break;
            default:
                $to = clone $deadlineDate;
        }

        if ($to < $dateFrom) {
            $to = clone $dateFrom;
        }
        return $to;
    }
}