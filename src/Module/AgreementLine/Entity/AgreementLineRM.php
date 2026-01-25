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
        new ORM\Index(name: "idx_has_production", columns: ["has_production"]),
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

    #[ORM\Column(type: 'boolean')]
    private bool $hasProduction;

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

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dpt01StartDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dpt01EndDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dpt02StartDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dpt02EndDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dpt03StartDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dpt03EndDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dpt04StartDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dpt04EndDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dpt05StartDate;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $dpt05EndDate;

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

    #[ORM\Column(type: 'text')]
    private string $q;

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

    public function hasProduction(): bool
    {
        return $this->hasProduction;
    }

    public function setHasProduction(bool $hasProduction): void
    {
        $this->hasProduction = $hasProduction;
    }

    public function getDpt01StartDate(): ?DateTimeInterface
    {
        return $this->dpt01StartDate;
    }

    public function setDpt01StartDate(?DateTimeInterface $dpt01StartDate): void
    {
        $this->dpt01StartDate = $dpt01StartDate;
    }

    public function getDpt01EndDate(): ?DateTimeInterface
    {
        return $this->dpt01EndDate;
    }

    public function setDpt01EndDate(?DateTimeInterface $dpt01EndDate): void
    {
        $this->dpt01EndDate = $dpt01EndDate;
    }

    public function getDpt02StartDate(): ?DateTimeInterface
    {
        return $this->dpt02StartDate;
    }

    public function setDpt02StartDate(?DateTimeInterface $dpt02StartDate): void
    {
        $this->dpt02StartDate = $dpt02StartDate;
    }

    public function getDpt02EndDate(): ?DateTimeInterface
    {
        return $this->dpt02EndDate;
    }

    public function setDpt02EndDate(?DateTimeInterface $dpt02EndDate): void
    {
        $this->dpt02EndDate = $dpt02EndDate;
    }

    public function getDpt03StartDate(): ?DateTimeInterface
    {
        return $this->dpt03StartDate;
    }

    public function setDpt03StartDate(?DateTimeInterface $dpt03StartDate): void
    {
        $this->dpt03StartDate = $dpt03StartDate;
    }

    public function getDpt03EndDate(): ?DateTimeInterface
    {
        return $this->dpt03EndDate;
    }

    public function setDpt03EndDate(?DateTimeInterface $dpt03EndDate): void
    {
        $this->dpt03EndDate = $dpt03EndDate;
    }

    public function getDpt04StartDate(): ?DateTimeInterface
    {
        return $this->dpt04StartDate;
    }

    public function setDpt04StartDate(?DateTimeInterface $dpt04StartDate): void
    {
        $this->dpt04StartDate = $dpt04StartDate;
    }

    public function getDpt04EndDate(): ?DateTimeInterface
    {
        return $this->dpt04EndDate;
    }

    public function setDpt04EndDate(?DateTimeInterface $dpt04EndDate): void
    {
        $this->dpt04EndDate = $dpt04EndDate;
    }

    public function getDpt05StartDate(): ?DateTimeInterface
    {
        return $this->dpt05StartDate;
    }

    public function setDpt05StartDate(?DateTimeInterface $dpt05StartDate): void
    {
        $this->dpt05StartDate = $dpt05StartDate;
    }

    public function getDpt05EndDate(): ?DateTimeInterface
    {
        return $this->dpt05EndDate;
    }

    public function setDpt05EndDate(?DateTimeInterface $dpt05EndDate): void
    {
        $this->dpt05EndDate = $dpt05EndDate;
    }

    public function getQ(): string
    {
        return $this->q;
    }

    public function setQ(string $q): void
    {
        $this->q = $q;
    }
}