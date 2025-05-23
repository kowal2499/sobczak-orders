<?php

namespace App\Module\Authorization\Entity;

use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\ValueObject\GrantOptionsCollection;
use App\Module\Authorization\ValueObject\GrantType;
use App\Module\ModuleRegistry\Entity\Module;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthGrantRepository::class)]
#[ORM\Table(name: "auth_grant")]
class AuthGrant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 40, unique: true)]
    private string $slug;

    #[ORM\ManyToOne(targetEntity: Module::class, inversedBy: null)]
    #[ORM\JoinColumn(nullable: false)]
    private Module $module;

    #[ORM\Column(type: "string", length: 60)]
    private string $name;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description;

    #[ORM\Column(type: "grant_type")]
    private GrantType $type;

    #[ORM\Column(type: "grant_options", nullable: true)]
    private ?GrantOptionsCollection $options;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
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

    public function getModule(): Module
    {
        return $this->module;
    }

    public function setModule(Module $module): void
    {
        $this->module = $module;
    }

    public function getOptions(): ?GrantOptionsCollection
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     */
    public function setOptions(?GrantOptionsCollection $options): void
    {
        $this->options = $options;
    }

    public function getType(): GrantType
    {
        return $this->type;
    }

    public function setType(GrantType $type): void
    {
        $this->type = $type;
    }
}
