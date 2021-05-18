<?php

namespace App\Service\Production;

use App\DTO\Production\ProductionTaskDTO;
use App\Entity\AgreementLine;
use App\Entity\Production;

class DefaultTaskCreateService
{
    public function create(ProductionTaskDTO $taskDTO, AgreementLine $agreementLine): Production
    {
        $deadline = new \DateTime($agreementLine->getConfirmedDate()->format('Y-m-d'));
        $production = new Production();
        $startDate = $taskDTO->getDateFrom() ?: $this->resolveDateFrom($taskDTO->getTaskSlug());

        $production->setDateStart($startDate);
        $production->setDateEnd(
            $taskDTO->getDateTo() ?: $this->resolveDateTo($taskDTO->getTaskSlug(), $deadline, $startDate)
        );

        $production->setStatus($taskDTO->getStatus());
        $production->setTitle($taskDTO->getTitle());
        $production->setCreatedAt(new \DateTime());

        return $production;
    }

    private function resolveDateFrom(string $taskSlug): \DateTime
    {
        return new \DateTime();
    }

    private function resolveDateTo(string $taskSlug, \DateTime $deadline, \DateTime $startDate): \DateTime
    {
        $to = $deadline;
        switch ($taskSlug) {
            case 'dpt01':
                $to = (clone $deadline)->modify('-7 days');
                if ($to < $startDate) {
                    $to = $startDate;
                }
                break;
        }
        return $to;
    }
}