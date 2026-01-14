<?php

namespace App\Module\AgreementLine\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class AgreementRM
{
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $status;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $orderNumber;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $createdDate;

    /**
     * @param int $id
     * @param string|null $status
     * @param string|null $orderNumber
     * @param DateTimeInterface $createdDate
     */
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
}