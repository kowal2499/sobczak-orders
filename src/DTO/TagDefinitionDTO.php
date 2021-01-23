<?php
/** @author: Roman Kowalski */

namespace App\DTO;

class TagDefinitionDTO
{
    private $name;
    private $module;
    private $icon;
    private $color;

    /**
     * TagDefinitionDTO constructor.
     * @param string $name
     * @param string $module
     * @param string|null $icon
     * @param string|null $color
     */
    public function __construct(
        string $name,
        string $module,
        ?string $icon = null,
        ?string $color = null)
    {
        $this->name = $name;
        $this->module = $module;
        $this->icon = $icon;
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    /**
     * @return string|null
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @param string|null $icon
     */
    public function setIcon(?string $icon): void
    {
        $this->icon = $icon;
    }

    /**
     * @param string|null $color
     */
    public function setColor(?string $color): void
    {
        $this->color = $color;
    }
}