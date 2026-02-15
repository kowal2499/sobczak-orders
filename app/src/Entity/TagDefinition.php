<?php
/** @author: Roman Kowalski */

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'tag_definition')]
#[ORM\Entity]
class TagDefinition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['_main', '_linePanel'])]
    private $id;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', length: 128, nullable: false)]
    #[Groups(['_main', '_linePanel'])]
    private $name;

    #[ORM\Column(type: 'string', length: 128, nullable: false)]
    #[Groups(['_main'])]
    private $module;

    #[ORM\Column(type: 'string', length: 128, nullable: true)]
    #[Groups(['_main'])]
    private $icon;

    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    #[Groups(['_main'])]
    private $color;

    #[ORM\Column(type: 'boolean')]
    private $isDeleted;

    #[ORM\OneToMany(targetEntity: \App\Entity\TagAssignment::class, mappedBy: 'tagDefinition')]
    private $tagAssignments;

    /**
     * @return mixed
     */
    public function getTagAssignments()
    {
        return $this->tagAssignments;
    }

    /**
     * TagDefinition constructor.
     * @param $name
     * @param $module
     * @param $icon
     * @param $color
     * @param bool $isDeleted
     */
    public function __construct($name, $module, $icon, $color, $isDeleted=false)
    {
        $this->name = $name;
        $this->module = $module;
        $this->icon = $icon;
        $this->color = $color;
        $this->isDeleted = $isDeleted;
        $this->tagAssignments = new ArrayCollection();
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

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    /**
     * @param bool $isDeleted
     */
    public function setIsDeleted(bool $isDeleted): void
    {
        $this->isDeleted = $isDeleted;
    }
}