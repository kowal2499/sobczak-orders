<?php

namespace App\Modules\Reports\Production\Mapper;

use App\Modules\Reports\Production\DTO\AgreementDTO;
use App\Modules\Reports\Production\DTO\AgreementLineDTO;
use App\Modules\Reports\Production\DTO\CustomerDTO;
use App\Modules\Reports\Production\DTO\TaskCompletedRecordDTO;

class TaskCompletedRecordMapper
{
    /**
     * Map raw repository row to TaskCompletedRecordDTO
     *
     * @param array<string, mixed> $row
     */
    public function mapRow(array $row): TaskCompletedRecordDTO
    {
        $agreementLine = new AgreementLineDTO(
            $row['id'] ?? null,
            $row['productName'] ?? null,
            $row['productionStartDate'] ?? null,
            $row['productionCompletionDate'] ?? null
        );

        $agreement = new AgreementDTO(
            $row['orderNumber'] ?? null,
            $row['confirmedDate'] ?? null
        );

        $customer = new CustomerDTO(
            $row['customerName'] ?? null
        );

        return new TaskCompletedRecordDTO(
            $row['departmentSlug'] ?? '',
            $row['factor'] ?? 0,
            $row['completedAt'] ?? null,
            $agreementLine,
            $agreement,
            $customer
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return TaskCompletedRecordDTO[]
     */
    public function mapMany(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRow($row);
        }
        return $items;
    }
}
