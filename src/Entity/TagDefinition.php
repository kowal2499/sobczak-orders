<?php
/** @author: Roman Kowalski */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="tag_definition")
 */
class TagDefinition
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25, nullable=false)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=128, nullable=false)
     */
    private $module;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $icon;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    private $color;

    /**
     * TagDefinition constructor.
     * @param $id
     * @param $name
     * @param $module
     * @param $icon
     * @param $color
     */
    public function __construct($name, $module, $icon, $color)
    {
        $this->name = $name;
        $this->module = $module;
        $this->icon = $icon;
        $this->color = $color;
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * @param mixed $module
     */
    public function setModule($module): void
    {
        $this->module = $module;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param mixed $icon
     */
    public function setIcon($icon): void
    {
        $this->icon = $icon;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     */
    public function setColor($color): void
    {
        $this->color = $color;
    }
}