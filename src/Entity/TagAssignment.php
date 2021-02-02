<?php
/** @author: Roman Kowalski */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Class TagAssignment
 * @package App\Entity
 * @ORM\Entity()
 * @ORM\Table(name="tag_assignment")
 */
class TagAssignment
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TagDefinition")
     */
    private $tagDefinition;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $user;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $contextId;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;

    /**
     * @return TagDefinition
     */
    public function getTagDefinition(): TagDefinition
    {
        return $this->tagDefinition;
    }

    /**
     * @param mixed $tagDefinition
     */
    public function setTagDefinition($tagDefinition): void
    {
        $this->tagDefinition = $tagDefinition;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param mixed $contextId
     */
    public function setContextId($contextId): void
    {
        $this->contextId = $contextId;
    }
}