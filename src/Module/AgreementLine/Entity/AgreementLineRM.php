<?php

namespace App\Module\AgreementLine\Entity;

use App\Module\AgreementLine\Repository\AgreementLineRMRepository;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgreementLineRMRepository::class)]
#[ORM\Table(
    name: "agreement_line_rm",
    indexes: [
        new ORM\Index(name: "idx_agreement_id", columns: ["agreement_id"]),
        new ORM\Index(name: "idx_customer_id", columns: ["customer_id"]),
        new ORM\Index(name: "idx_status", columns: ["status"]),
        new ORM\Index(name: "idx_is_deleted", columns: ["is_deleted"]),
        new ORM\Index(name: "idx_is_archived", columns: ["is_archived"]),
        new ORM\Index(name: "idx_confirmed_date", columns: ["confirmed_date"]),
        new ORM\Index(name: "idx_production_start_date", columns: ["production_start_date"]),
        new ORM\Index(name: "idx_production_end_date", columns: ["production_end_date"]),
        // Composite indexes for common queries (commented out for now)
        // new ORM\Index(name: "idx_customer_deleted", columns: ["customer_id", "is_deleted"]),
        // new ORM\Index(name: "idx_customer_archived", columns: ["customer_id", "is_archived"]),
        // new ORM\Index(name: "idx_status_confirmed", columns: ["status", "confirmed_date"]),
        // new ORM\Index(name: "idx_agreement_deleted", columns: ["agreement_id", "is_deleted"]),
    ]
)]
class AgreementLineRM
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: "NONE")]
    private int $agreementLineId;

    #[ORM\Column(type: 'integer')]
    private int $agreementId;

    #[ORM\Column(type: 'integer')]
    private int $customerId;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $status;

    #[ORM\Column(type: 'boolean')]
    private bool $isDeleted;

    #[ORM\Column(type: 'boolean')]
    private bool $isArchived;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $agreementCreateDate;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $confirmedDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $productionStartDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $productionEndDate;

    #[ORM\Column(type: 'json')]
    private array $userData;

    #[ORM\Column(type: 'json')]
    private array $customerData;

    #[ORM\Column(type: 'json')]
    private array $productData;

    #[ORM\Column(type: 'json')]
    private array $agreementData;

    #[ORM\Column(type: 'json')]
    private array $productionsData;

    public function __construct(int $agreementLineId)
    {
        $this->agreementLineId = $agreementLineId;
        $this->userData = [];
        $this->customerData = [];
        $this->productData = [];
        $this->agreementData = [];
        $this->productionsData = [];
    }

    public function getAgreementLineId(): int
    {
        return $this->agreementLineId;
    }

    public function getAgreementId(): int
    {
        return $this->agreementId;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
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
        return !empty($this->userData) ? UserRM::fromArray($this->userData) : new UserRM();
    }

    public function setUser(?UserRM $user): void
    {
        $this->userData = $user ? $user->toArray() : [];
    }

    public function getCustomer(): CustomerRM
    {
        return CustomerRM::fromArray($this->customerData);
    }

    public function setCustomer(CustomerRM $customer): void
    {
        $this->customerData = $customer->toArray();
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

    public function setAgreementCreateDate(DateTimeInterface $agreementCreateDate): void
    {
        $this->agreementCreateDate = $agreementCreateDate;
    }

    public function setProductionStartDate(?DateTimeInterface $productionStartDate): void
    {
        $this->productionStartDate = $productionStartDate;
    }

    public function setProductionEndDate(?DateTimeInterface $productionEndDate): void
    {
        $this->productionEndDate = $productionEndDate;
    }

    public function getProduct(): ProductRM
    {
        return ProductRM::fromArray($this->productData);
    }

    public function setProduct(ProductRM $product): void
    {
        $this->productData = $product->toArray();
    }

    public function getAgreement(): AgreementRM
    {
        return AgreementRM::fromArray($this->agreementData);
    }

    public function setAgreement(AgreementRM $agreement): void
    {
        $this->agreementData = $agreement->toArray();
    }

    public function getProductions(): array
    {
        return array_map(
            fn(array $data) => ProductionRM::fromArray($data),
            $this->productionsData
        );
    }

    public function setProductions(array $productions): void
    {
        $this->productionsData = array_map(
            fn(ProductionRM $production) => $production->toArray(),
            $productions
        );
    }

    public function addProduction(ProductionRM $production): void
    {
        $this->productionsData[] = $production->toArray();
    }

    public function getProductionByDepartment(string $departmentSlug): ?ProductionRM
    {
        foreach ($this->productionsData as $productionData) {
            if (isset($productionData['departmentSlug']) && $productionData['departmentSlug'] === $departmentSlug) {
                return ProductionRM::fromArray($productionData);
            }
        }
        return null;
    }

    public function setAgreementId(int $agreementId): void
    {
        $this->agreementId = $agreementId;
    }

    public function getAgreementCreateDate(): DateTimeInterface
    {
        return $this->agreementCreateDate;
    }

    public function setCustomerId(int $customerId): void
    {
        $this->customerId = $customerId;
    }
}