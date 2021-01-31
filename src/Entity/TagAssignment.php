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
    private $createdBy;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $contextId;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    private $createdAt;
}