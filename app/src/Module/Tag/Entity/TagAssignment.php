<?php

/** @author: Roman Kowalski */

namespace App\Module\Tag\Entity;

use App\Entity\AgreementLine;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'tag_assignment')]
#[ORM\Entity]
class TagAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: TagDefinition::class, inversedBy: 'tagAssignments')]
    #[Groups(['_main', '_linePanel'])]
    private $tagDefinition;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(['_main', '_linePanel'])]
    private $user;

    #[ORM\JoinColumn(name: 'context_id')]
    #[ORM\ManyToOne(targetEntity: AgreementLine::class, inversedBy: 'tags')]
    private $contextId;

    /**
     * @Gedmo\Timestampable(on="create")
     */
    #[ORM\Column(type: 'datetime')]
    #[Groups(['_main', '_linePanel'])]
    private $createdAt;

    public function getTagDefinition(): TagDefinition
    {
        return $this->tagDefinition;
    }

    public function setTagDefinition($tagDefinition): void
    {
        $this->tagDefinition = $tagDefinition;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function setContextId(AgreementLine $contextId): void
    {
        $this->contextId = $contextId;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getContextId(): AgreementLine
    {
        return $this->contextId;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
