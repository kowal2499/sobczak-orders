<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 */
class Product
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="float")
     */
    private $factor = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createDate;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\AgreementLine", mappedBy="Product")
     */
    private $agreementLines;

    public function __construct()
    {
        $this->agreementLines = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTimeInterface $createDate): self
    {
        $this->createDate = $createDate;

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

    /**
     * @return Collection|AgreementLine[]
     */
    public function getAgreementLines(): Collection
    {
        return $this->agreementLines;
    }

    public function addAgreementLine(AgreementLine $agreementLine): self
    {
        if (!$this->agreementLines->contains($agreementLine)) {
            $this->agreementLines[] = $agreementLine;
            $agreementLine->setProduct($this);
        }

        return $this;
    }

    public function removeAgreementLine(AgreementLine $agreementLine): self
    {
        if ($this->agreementLines->contains($agreementLine)) {
            $this->agreementLines->removeElement($agreementLine);
            // set the owning side to null (unless already changed)
            if ($agreementLine->getProduct() === $this) {
                $agreementLine->setProduct(null);
            }
        }

        return $this;
    }
}
