<?php

namespace App\Module\Task\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\MappedSuperclass]
abstract class BaseTask
{
    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['_main', '_linePanel'])]
    protected ?\DateTimeInterface $dateStart = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['_main', '_linePanel'])]
    protected ?\DateTimeInterface $dateEnd = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['_main', '_linePanel'])]
    protected ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['_main', '_linePanel'])]
    protected ?string $description = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(['_main', '_linePanel'])]
    protected ?bool $isStartDelayed = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    #[Groups(['_main', '_linePanel'])]
    protected ?bool $isCompleted = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['_main', '_linePanel'])]
    protected ?\DateTimeInterface $completedAt = null;

    /**
     * @Gedmo\Timestampable(on="create")
     */
    #[ORM\Column(type: 'datetime')]
    #[Groups(['_main', '_linePanel'])]
    protected \DateTimeInterface $createdAt;

    /**
     * @Gedmo\Timestampable(on="update")
     */
    #[ORM\Column(type: 'datetime')]
    #[Groups(['_main', '_linePanel'])]
    protected \DateTimeInterface $updatedAt;

    public function getDateStart(): ?\DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(?\DateTimeInterface $dateStart): self
    {
        $this->dateStart = $dateStart;
        return $this;
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(?\DateTimeInterface $dateEnd): self
    {
        $this->dateEnd = $dateEnd;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function isStartDelayed(): ?bool
    {
        return $this->isStartDelayed;
    }

    public function getIsStartDelayed(): ?bool
    {
        return $this->isStartDelayed;
    }

    public function setIsStartDelayed(?bool $isStartDelayed): self
    {
        $this->isStartDelayed = $isStartDelayed;
        return $this;
    }

    public function isCompleted(): ?bool
    {
        return $this->isCompleted;
    }

    public function getIsCompleted(): ?bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(?bool $isCompleted): self
    {
        $this->isCompleted = $isCompleted;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
