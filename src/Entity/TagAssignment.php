<?php
/** @author: Roman Kowalski */

namespace App\Entity;

use App\Repository\AgreementLineRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

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
     *
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TagDefinition", inversedBy="tagAssignments")
     */
    #[Groups(['_main', '_linePanel'])]
    private $tagDefinition;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    #[Groups(['_main', '_linePanel'])]
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AgreementLine", inversedBy="tags")
     * @ORM\JoinColumn(name="context_id")
     */
    private $contextId;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    #[Groups(['_main', '_linePanel'])]
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
     * @param AgreementLine $contextId
     */
    public function setContextId(AgreementLine $contextId): void
    {
        $this->contextId = $contextId;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return AgreementLine
     */
    public function getContextId(): AgreementLine
    {
        return $this->contextId;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }


}