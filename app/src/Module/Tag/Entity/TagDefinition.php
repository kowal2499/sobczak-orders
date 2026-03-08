<?php
/** @author: Roman Kowalski */

namespace App\Module\Tag\Entity;

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

    #[ORM\Column(type: 'string', length: 160, nullable: false)]
    #[Groups(['_main'])]
    private string $slug;

    #[ORM\Column(type: 'boolean')]
    private $isDeleted;

    #[ORM\OneToMany(targetEntity: TagAssignment::class, mappedBy: 'tagDefinition')]
    private $tagAssignments;

    public function __construct($name, $module, $icon, $color, string $slug = '', $isDeleted = false)
    {
        $this->name = $name;
        $this->module = $module;
        $this->icon = $icon;
        $this->color = $color;
        $this->slug = $slug;
        $this->isDeleted = $isDeleted;
        $this->tagAssignments = new ArrayCollection();
    }

    public function getTagAssignments()
    {
        return $this->tagAssignments;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getModule()
    {
        return $this->module;
    }

    public function setModule($module): void
    {
        $this->module = $module;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function setIcon($icon): void
    {
        $this->icon = $icon;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function setColor($color): void
    {
        $this->color = $color;
    }

    public function isDeleted(): bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): void
    {
        $this->isDeleted = $isDeleted;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }
}
