<?php

namespace App\Service\Production;
use App\Entity\Production;

class ProductionTaskDatesResolver
{
    public function resolveDateFrom(): \DateTimeInterface
    {
        return new \DateTime();
    }

    public function resolveDateTo(Production $production): \DateTimeInterface
    {
        $agreementLine = $production->getAgreementLine();
        if (!$agreementLine) {
            throw new \LogicException('Agreement line must exists.');
        }

        $to = clone($agreementLine->getConfirmedDate());
        switch ($production->getDepartmentSlug()) {
            case 'dpt01':
                $to->modify('-7 days');
                if ($to < $production->getDateStart()) {
                    $to = clone($production->getDateStart());
                }
                break;
        }
        return $to;
    }
}