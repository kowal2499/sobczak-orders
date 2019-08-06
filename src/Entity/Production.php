<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductionRepository")
 */
class Production
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
    private $departmentSlug;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateStart;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateEnd;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AgreementLine", inversedBy="productions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $agreementLine;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\StatusLog", mappedBy="production", orphanRemoval=true)
     */
    private $statusLogs;

    public function __construct()
    {
        $this->statusLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepartmentSlug(): ?string
    {
        return $this->departmentSlug;
    }

    public function setDepartmentSlug(string $departmentSlug): self
    {
        $this->departmentSlug = $departmentSlug;

        return $this;
    }

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(\DateTimeInterface $dateStart): self
    {
        $this->dateStart = $dateStart;

        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(\DateTimeInterface $dateEnd): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAgreementLine(): ?AgreementLine
    {
        return $this->agreementLine;
    }

    public function setAgreementLine(?AgreementLine $agreementLine): self
    {
        $this->agreementLine = $agreementLine;

        return $this;
    }

    /**
     * @return Collection|StatusLog[]
     */
    public function getStatusLogs(): Collection
    {
        return $this->statusLogs;
    }

    public function addStatusLog(StatusLog $statusLog): self
    {
        if (!$this->statusLogs->contains($statusLog)) {
            $this->statusLogs[] = $statusLog;
            $statusLog->setProduction($this);
        }

        return $this;
    }

    public function removeStatusLog(StatusLog $statusLog): self
    {
        if ($this->statusLogs->contains($statusLog)) {
            $this->statusLogs->removeElement($statusLog);
            // set the owning side to null (unless already changed)
            if ($statusLog->getProduction() === $this) {
                $statusLog->setProduction(null);
            }
        }

        return $this;
    }
}
