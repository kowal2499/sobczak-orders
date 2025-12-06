<?php

namespace App\Module\Production\Entity;

use App\Entity\Production;
use App\Module\Production\Repository\FactorAdjustmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: FactorAdjustmentRepository::class)]
#[ORM\Table(name: "production_factor_adjustment")]
class FactorAdjustment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    #[Groups(['_main', '_linePanel'])]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Production::class, inversedBy: 'factorAdjustments', cascade: ['persist', 'remove'])]
    private Production $production;

    #[ORM\Column(type: 'string', length: 512, nullable: false)]
    #[Groups(['_main', '_linePanel'])]
    private string $description;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Groups(['_main', '_linePanel'])]
    private float $factor;

    public function getId(): int
    {
        return $this->id;
    }

    public function getProduction(): Production
    {
        return $this->production;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getFactor(): float
    {
        return $this->factor;
    }

    public function setProduction(Production $production): void
    {
        $this->production = $production;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function setFactor(float $factor): void
    {
        $this->factor = $factor;
    }
}