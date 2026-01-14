<?php

namespace App\Module\AgreementLine\Entity;

use App\Module\AgreementLine\Repository\AgreementLineRMRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgreementLineRMRepository::class)]
#[ORM\Table(
    name: "agreement_line_rm",
    indexes: [
        new ORM\Index(columns: ["id"], name: "idx_agreement_line_id"),
        new ORM\Index(columns: ["confirmed_date"], name: "idx_agreement_line_confirmed_date")
    ]
)]
class AgreementLineRM
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $status;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted;

    #[ORM\Column(type: 'boolean')]
    private bool $isArchived;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $confirmedDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $productionStartDate;
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $productionEndDate;

    #[ORM\Embedded(class: UserRM::class, columnPrefix: 'user_')]
    private UserRM $user;

    #[ORM\Embedded(class: CustomerRM::class, columnPrefix: 'customer_')]
    private CustomerRM $customer;

    #[ORM\Embedded(class: ProductRM::class, columnPrefix: 'product_')]
    private ProductRM $product;

    #[ORM\Embedded(class: AgreementRM::class, columnPrefix: 'agreement_')]
    private AgreementRM $agreement;

    #[ORM\Embedded(class: ProductionRM::class, columnPrefix: 'dpt01_')]
    private ProductionRM $dpt01;

    #[ORM\Embedded(class: ProductionRM::class, columnPrefix: 'dpt02_')]
    private ProductionRM $dpt02;

    #[ORM\Embedded(class: ProductionRM::class, columnPrefix: 'dpt03_')]
    private ProductionRM $dpt03;

    #[ORM\Embedded(class: ProductionRM::class, columnPrefix: 'dpt04_')]
    private ProductionRM $dpt04;

    #[ORM\Embedded(class: ProductionRM::class, columnPrefix: 'dpt05_')]
    private ProductionRM $dpt05;


    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function getConfirmedDate(): DateTimeInterface
    {
        return $this->confirmedDate;
    }

    public function getProductionStartDate(): ?DateTimeInterface
    {
        return $this->productionStartDate;
    }

    public function getProductionEndDate(): ?DateTimeInterface
    {
        return $this->productionEndDate;
    }

    public function getUser(): UserRM
    {
        return $this->user;
    }

    public function getCustomer(): CustomerRM
    {
        return $this->customer;
    }

    public function getProduct(): ProductRM
    {
        return $this->product;
    }

    public function getAgreement(): AgreementRM
    {
        return $this->agreement;
    }

    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }

    public function setIsDeleted(bool $isDeleted): void
    {
        $this->isDeleted = $isDeleted;
    }

    public function setIsArchived(bool $isArchived): void
    {
        $this->isArchived = $isArchived;
    }

    public function setConfirmedDate(DateTimeInterface $confirmedDate): void
    {
        $this->confirmedDate = $confirmedDate;
    }

    public function setProductionStartDate(?DateTimeInterface $productionStartDate): void
    {
        $this->productionStartDate = $productionStartDate;
    }

    public function setProductionEndDate(?DateTimeInterface $productionEndDate): void
    {
        $this->productionEndDate = $productionEndDate;
    }

    public function setUser(UserRM $user): void
    {
        $this->user = $user;
    }

    public function setCustomer(CustomerRM $customer): void
    {
        $this->customer = $customer;
    }

    public function setProduct(ProductRM $product): void
    {
        $this->product = $product;
    }

    public function setAgreement(AgreementRM $agreement): void
    {
        $this->agreement = $agreement;
    }

    public function setDpt01(ProductionRM $dpt01): void
    {
        $this->dpt01 = $dpt01;
    }

    public function setDpt02(ProductionRM $dpt02): void
    {
        $this->dpt02 = $dpt02;
    }

    public function setDpt03(ProductionRM $dpt03): void
    {
        $this->dpt03 = $dpt03;
    }

    public function setDpt04(ProductionRM $dpt04): void
    {
        $this->dpt04 = $dpt04;
    }

    public function setDpt05(ProductionRM $dpt05): void
    {
        $this->dpt05 = $dpt05;
    }
}