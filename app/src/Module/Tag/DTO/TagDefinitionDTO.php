<?php

/** @author: Roman Kowalski */

namespace App\Module\Tag\DTO;

class TagDefinitionDTO
{
    private string $name;
    private string $module;
    private ?string $icon;
    private ?string $color;
    private ?string $slug;

    public function __construct(
        string $name,
        string $module,
        ?string $icon = null,
        ?string $color = null,
        ?string $slug = null
    ) {
        $this->name = $name;
        $this->module = $module;
        $this->icon = $icon;
        $this->color = $color;
        $this->slug = $slug;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): void
    {
        $this->slug = $slug;
    }
}
