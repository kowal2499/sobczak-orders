<?php

namespace App\Modules\Reports\Production;

interface RecordSupplierInterface
{
    public function getId(): string;
    public function getTitle(): string;

    public function getSummary(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end
    ): array;

    public function getRecords(
        ?\DateTimeInterface $start,
        ?\DateTimeInterface $end,
        array $departments = []
    ): array;
}