<?php

namespace App\Module\AgreementLine\Entity;

use DateTime;
use DateTimeInterface;

class AgreementRM
{
    private int $id;
    private ?string $status;
    private ?string $orderNumber;
    private DateTimeInterface $createdDate;

    public function __construct(
        int $id,
        DateTimeInterface $createdDate,
        ?string $status = null,
        ?string $orderNumber = null,
    ) {
        $this->id = $id;
        $this->createdDate = $createdDate;
        $this->status = $status;
        $this->orderNumber = $orderNumber;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function getCreatedDate(): DateTimeInterface
    {
        return $this->createdDate;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'orderNumber' => $this->orderNumber,
            'createdDate' => $this->createdDate->format('Y-m-d H:i:s'),
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            createdDate: new DateTime($data['createdDate']),
            status: $data['status'] ?? null,
            orderNumber: $data['orderNumber'] ?? null,
        );
    }
}
