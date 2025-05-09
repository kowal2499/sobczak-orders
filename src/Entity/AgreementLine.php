<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AgreementLineRepository")
 */
class AgreementLine
{
    const STATUS_WAITING = 5;
    const STATUS_MANUFACTURING = 10;
    const STATUS_WAREHOUSE = 15;
    const STATUS_ARCHIVED = 20;
    const STATUS_DELETED = 25;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    #[Groups(['_main', '_linePanel'])]
    private int $id;

    /**
     * @ORM\Column(type="datetime")
     */
    #[Groups(['_main', '_linePanel'])]
    private $confirmedDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Product", inversedBy="agreementLines")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Groups('_main')]
    private $Product;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Agreement", inversedBy="agreementLines")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Groups('_main')]
    private $Agreement;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Production", cascade={"persist", "remove"}, mappedBy="agreementLine", orphanRemoval=true)
     * @ORM\OrderBy({"id" = "ASC"})
     */
    #[Groups(['_main', '_linePanel'])]
    private $productions;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $archived;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    #[Groups(['_main', '_linePanel'])]
    private ?int $status;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $deleted;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(['_main', '_linePanel'])]
    private $description;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    #[Groups(['_main', '_linePanel'])]
    private $factor;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TagAssignment", mappedBy="contextId")
     */
    #[Groups(['_main', '_linePanel'])]
    private $tags;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Groups(['_main', '_linePanel'])]
    private $productionStartDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Groups(['_main', '_linePanel'])]
    private $productionCompletionDate;

    public function __construct()
    {
        $this->productions = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    /**
     * @return Collection|TagAssignment[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConfirmedDate(): ?\DateTimeInterface
    {
        return $this->confirmedDate;
    }

    public function setConfirmedDate(\DateTimeInterface $confirmedDate): self
    {
        $this->confirmedDate = $confirmedDate;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->Product;
    }

    public function setProduct(?Product $Product): self
    {
        $this->Product = $Product;

        return $this;
    }

    public function getAgreement(): ?Agreement
    {
        return $this->Agreement;
    }

    public function setAgreement(?Agreement $Agreement): self
    {
        $this->Agreement = $Agreement;

        return $this;
    }

    /**
     * @return Collection|Production[]
     */
    public function getProductions(): Collection
    {
        return $this->productions;
    }

    public function addProduction(Production $production): self
    {
        if (!$this->productions->contains($production)) {
            $this->productions[] = $production;
            $production->setAgreementLine($this);
        }

        return $this;
    }

    public function removeProduction(Production $production): self
    {
        if ($this->productions->contains($production)) {
            $this->productions->removeElement($production);
            // set the owning side to null (unless already changed)
            if ($production->getAgreementLine() === $this) {
                $production->setAgreementLine(null);
            }
        }

        return $this;
    }

    public function getArchived(): ?bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): self
    {
        $this->archived = $archived;

        return $this;
    }

    public function getDeleted(): ?bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getFactor(): ?float
    {
        return $this->factor;
    }

    public function setFactor(?float $factor): self
    {
        $this->factor = $factor;

        return $this;
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_WAITING => 'Oczekujące',
            self::STATUS_MANUFACTURING => 'W realizacji',
            self::STATUS_WAREHOUSE => 'Magazyn',
            self::STATUS_ARCHIVED => 'Archiwum',
            self::STATUS_DELETED => 'Kosz'
        ];
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getProductionCompletionDate(): ?\DateTimeInterface
    {
        return $this->productionCompletionDate;
    }

    public function setProductionCompletionDate(?\DateTimeInterface $productionCompletionDate): self
    {
        $this->productionCompletionDate = $productionCompletionDate;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getProductionStartDate(): ?\DateTimeInterface
    {
        return $this->productionStartDate;
    }

    /**
     * @param \DateTimeInterface|null $productionStartDate
     */
    public function setProductionStartDate(?\DateTimeInterface $productionStartDate): void
    {
        $this->productionStartDate = $productionStartDate;
    }
}
