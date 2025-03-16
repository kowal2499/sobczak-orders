<?php

namespace App\Entity;

use App\Repository\AgreementRepository;
use App\Service\UploaderHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AgreementRepository")
 */
class Agreement
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    #[Groups('_main')]
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    #[Groups('_main')]
    private $createDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updateDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Customer", inversedBy="agreements")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Groups('_main')]
    private $Customer;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\AgreementLine", mappedBy="Agreement", cascade={"remove"})
     */
    private $agreementLines;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    #[Groups('_main')]
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups('_main')]
    private $orderNumber;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Attachment", mappedBy="Agreement", orphanRemoval=true)
     */
    #[Groups('_main')]
    private $attachments;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=true)
     */
    #[Groups('_main')]
    private $user = null;

    public function __construct()
    {
        $this->agreementLines = new ArrayCollection();
        $this->attachments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTimeInterface $createDate): self
    {
        $this->createDate = $createDate;
        return $this;
    }

    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->updateDate;
    }

    public function setUpdateDate(\DateTimeInterface $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->Customer;
    }

    public function setCustomer(?Customer $Customer): self
    {
        $this->Customer = $Customer;

        return $this;
    }

    /**
     * @return Collection|AgreementLine[]
     */
    public function getAgreementLines(): Collection
    {
        return $this->agreementLines;
    }

    public function addAgreementLine(AgreementLine $agreementLine): self
    {
        if (!$this->agreementLines->contains($agreementLine)) {
            $this->agreementLines[] = $agreementLine;
            $agreementLine->setAgreement($this);
        }

        return $this;
    }

    public function removeAgreementLine(AgreementLine $agreementLine): self
    {
        if ($this->agreementLines->contains($agreementLine)) {
            $this->agreementLines->removeElement($agreementLine);
            // set the owning side to null (unless already changed)
            if ($agreementLine->getAgreement() === $this) {
                $agreementLine->setAgreement(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(?string $orderNumber): self
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    /**
     * @return Collection|Attachment[]
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(Attachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments[] = $attachment;
            $attachment->setAgreement($this);
        }

        return $this;
    }

    public function removeAttachment(Attachment $attachment): self
    {
        if ($this->attachments->contains($attachment)) {
            $this->attachments->removeElement($attachment);
            // set the owning side to null (unless already changed)
            if ($attachment->getAgreement() === $this) {
                $attachment->setAgreement(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }
}
