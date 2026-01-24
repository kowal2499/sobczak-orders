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

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $userName;

    #[ORM\Column(type: 'string', length: 64)]
    private string $orderNumber;

    #[ORM\Column(type: 'string', length: 126)]
    private string $customerName;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $productName;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $factor;

    #[ORM\Column(type: 'json')]
    private array $user;

    #[ORM\Column(type: 'json')]
    private array $customer;

    #[ORM\Column(type: 'json')]
    private array $product;

    #[ORM\Column(type: 'json')]
    private array $agreement;

    #[ORM\Column(type: 'json')]
    private array $productions;

    #[ORM\Column(type: 'json')]
    private array $tags;

    #[ORM\Column(type: 'json')]
    private array $attachments;

    public function __construct(int $agreementLineId)
    {
        $this->agreementLineId = $agreementLineId;
        $this->user = [];
        $this->customer = [];
        $this->product = [];
        $this->agreement = [];
        $this->productions = [];
        $this->tags = [];
        $this->attachments = [];
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
        return !empty($this->user) ? UserRM::fromArray($this->user) : new UserRM();
    }

    public function setUser(?UserRM $user): void
    {
        $this->user = $user ? $user->toArray() : [];
    }

    public function getCustomer(): CustomerRM
    {
        return CustomerRM::fromArray($this->customer);
    }

    public function setCustomer(CustomerRM $customer): void
    {
        $this->customer = $customer->toArray();
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
        return ProductRM::fromArray($this->product);
    }

    public function setProduct(ProductRM $product): void
    {
        $this->product = $product->toArray();
    }

    public function getAgreement(): AgreementRM
    {
        return AgreementRM::fromArray($this->agreement);
    }

    public function setAgreement(AgreementRM $agreement): void
    {
        $this->agreement = $agreement->toArray();
    }

    /**
     * @return ProductionRM[]
     */
    public function getProductions(): array
    {
        return array_map(
            fn(array $data) => ProductionRM::fromArray($data),
            $this->productions
        );
    }

    /**
     * @param ProductionRM[] $productions
     */
    public function setProductions(array $productions): void
    {
        $this->productions = array_map(
            fn(ProductionRM $production) => $production->toArray(),
            $productions
        );
    }

    public function addProduction(ProductionRM $production): void
    {
        $this->productions[] = $production->toArray();
    }

    public function getProductionByDepartment(string $departmentSlug): ?ProductionRM
    {
        foreach ($this->productions as $productionData) {
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

    /**
     * @return TagRM[]
     */
    public function getTags(): array
    {
        return array_map(fn ($tag) => TagRM::fromArray($tag), $this->tags);
    }

    /**
     * @param TagRM[] $tags
     * @return void
     */
    public function setTags(array $tags): void
    {
        $this->tags = array_map(fn (TagRM $tag) => $tag->toArray(), $tags);
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(?string $userName): void
    {
        $this->userName = $userName;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): void
    {
        $this->orderNumber = $orderNumber;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): void
    {
        $this->customerName = $customerName;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(?string $productName): void
    {
        $this->productName = $productName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getFactor(): ?float
    {
        return $this->factor;
    }

    public function setFactor(?float $factor): void
    {
        $this->factor = $factor;
    }

    /**
     * @return AttachmentRM[]
     */
    public function getAttachments(): array
    {
        return array_map(
            fn ($attachment) => AttachmentRM::fromArray($attachment),
            $this->attachments
        );
    }

    /**
     * @param AttachmentRM[] $attachments
     * @return void
     */
    public function setAttachments(array $attachments): void
    {
        $this->attachments = array_map(
            fn (AttachmentRM $attachment) => $attachment->toArray(),
            $attachments
        );
    }
}