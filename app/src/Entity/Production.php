<?php

namespace App\Entity;

use App\Module\Production\ValueObject\DepartmentEnum;
use App\Module\Task\Entity\BaseTask;
use App\Repository\ProductionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: ProductionRepository::class)]
class Production extends BaseTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['_main', '_linePanel'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['_main', '_linePanel'])]
    private string $departmentSlug;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    #[Groups(['_main', '_linePanel'])]
    private $status;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['_main', '_linePanel'])]
    private bool $isGhost = false;


    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: AgreementLine::class, inversedBy: 'productions')]
    private $agreementLine;

    #[ORM\OneToMany(mappedBy: 'production', targetEntity: StatusLog::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[OrderBy(['createdAt' => 'ASC'])]
    #[Groups(['_main', '_linePanel'])]
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;


        return $this;
    }

    public function isGhost(): bool
    {
        return $this->isGhost;
    }

    public function getIsGhost(): bool
    {
        return $this->isGhost;
    }

    public function setIsGhost(bool $isGhost): self
    {
        $this->isGhost = $isGhost;

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
     * Nadpisanie getTitle() z BaseTask aby zwracało nazwę działu gdy title jest null
     */
    public function getTitle(): ?string
    {
        if ($this->title !== null) {
            return $this->title;
        }

        // Dla starych rekordów Production bez title zwróć nazwę działu
        try {
            $dept = DepartmentEnum::from($this->departmentSlug);
            return $dept->getName();
        } catch (\ValueError $e) {
            return null;
        }
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
