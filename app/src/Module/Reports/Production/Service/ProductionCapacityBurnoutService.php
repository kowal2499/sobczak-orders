<?php

namespace App\Module\Reports\Production\Service;

use App\Entity\AgreementLine;
use App\Module\AgreementLine\Repository\AgreementLineRMRepository;
use App\Module\Reports\Production\DTO\CapacityBurnoutDTO;

class ProductionCapacityBurnoutService
{

    public function __construct(
        private readonly AgreementLineRMRepository $agreementLineRepo,
    ) {
    }

    public function calculateBurnout(\DateTimeImmutable $start, \DateTimeImmutable $end): CapacityBurnoutDTO
    {
        $agreementLines = $this->agreementLineRepo->search([
            'search' => [
            'hasProduction' => true,
            'dateDelivery' => [
                'start' => $start->format('Y-m-d'),
                'end' => $end->format('Y-m-d'),
            ],
            'status' => [
                AgreementLine::STATUS_WAREHOUSE,
                AgreementLine::STATUS_MANUFACTURING,
                AgreementLine::STATUS_ARCHIVED
            ],
                ],
            'sort' => [
                'dateConfirmed' => 'ASC',
            ],

        ])->getResult();

        return new CapacityBurnoutDTO(
            $start,
            $end,
            null,
            null,
            $agreementLines,
        );
    }
}