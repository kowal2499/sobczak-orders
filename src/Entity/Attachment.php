<?php

namespace App\Entity;

use App\Service\UploaderHelper;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass="App\Repository\AttachmentRepository")
 */
class Attachment
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=1024)
     * @Groups("_main")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=1024)
     * @Groups("_main")
     */
    // todo: used to be 'original'
    private $originalName;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Agreement", inversedBy="attachments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Agreement;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     * @Groups("_main")
     */
    private $extension;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): self
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getAgreement(): ?Agreement
    {
        return $this->Agreement;
    }

    public function setAgreement(?Agreement $Agreement): self
    {
        $this->Agreement = $Agreement;

        return $this;
    }

    public function getPath()
    {
        return UploaderHelper::AGREEMENTS_PATH . '/' . $this->getName();
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }
}
