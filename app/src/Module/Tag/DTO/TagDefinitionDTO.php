<?php
/** @author: Roman Kowalski */

namespace App\Module\Tag\DTO;

class TagDefinitionDTO
{
    private string $name;
    private string $module;
    private ?string $icon;
    private ?string $color;

    public function __construct(
        string $name,
        string $module,
        ?string $icon = null,
        ?string $color = null
    ) {
        $this->name = $name;
        $this->module = $module;
        $this->icon = $icon;
        $this->color = $color;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    public function setColor(?string $color): void
    {
        $this->color = $color;
    }
}
