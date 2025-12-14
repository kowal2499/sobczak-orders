<?php

namespace App\Module\Production\Entity;

use App\Entity\AgreementLine;
use App\Module\Production\Repository\FactorRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: FactorRepository::class)]
#[ORM\Table(name: "factor")]
class Factor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: AgreementLine::class, inversedBy: 'factors', cascade: ['persist', 'remove'])]
    private AgreementLine $agreementLine;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private string $departmentSlug;

    #[ORM\Column(type: 'string', enumType: FactorSource::class)]
    private FactorSource $source;

    #[ORM\Column(type: 'float', nullable: true)]
    private float $factorValue;

    #[ORM\Column(type: 'string', length: 512, nullable: true)]
    private string $description;

    /** @Gedmo\Timestampable(on="create") */
    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function getId(): int
    {
        return $this->id;
    }

    public function getAgreementLine(): AgreementLine
    {
        return $this->agreementLine;
    }

    public function getDepartmentSlug(): string
    {
        return $this->departmentSlug;
    }

    public function getType(): FactorSource
    {
        return $this->type;
    }

    public function getFactorValue(): float
    {
        return $this->factorValue;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSource(): FactorSource
    {
        return $this->source;
    }

    public function setSource(FactorSource $source): void
    {
        $this->source = $source;
    }

    public function setFactorValue(float $factorValue): void
    {
        $this->factorValue = $factorValue;
    }
}