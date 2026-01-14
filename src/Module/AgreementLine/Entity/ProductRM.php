<?php

namespace App\Module\AgreementLine\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class ProductRM
{
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'float')]
    private float $factor;

    /**
     * @param int $id
     * @param string $name
     * @param float $factor
     */
    public function __construct(
        int $id,
        string $name,
        float $factor
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->factor = $factor;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFactor(): float
    {
        return $this->factor;
    }
}