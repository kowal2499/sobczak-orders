<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups('_main')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Pole nie może być puste')]
    #[Groups('_main')]
    private $name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups('_main')]
    private $first_name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups('_main')]
    private $last_name;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups('_main')]
    private $street;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups('_main')]
    private $street_number;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups('_main')]
    private $apartment_number;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups('_main')]
    private $city;

    #[ORM\Column(type: 'string', length: 16, nullable: true)]
    #[Groups('_main')]
    private $postal_code;

    #[ORM\Column(type: 'string', length: 2, nullable: true)]
    #[Groups('_main')]
    private $country;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups('_main')]
    private $phone;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups('_main')]
    private $email;

    #[ORM\Column(type: 'datetime')]
    #[Groups('_main')]
    private $create_date;

    #[ORM\Column(type: 'datetime')]
    #[Groups('_main')]
    private $update_date;

    #[ORM\OneToMany(targetEntity: Agreement::class, mappedBy: 'Customer', orphanRemoval: true)]
    private $agreements;

    #[ORM\OneToMany(targetEntity: Customers2Users::class, mappedBy: 'customer')]
    private $customers2Users;

    public function __construct()
    {
        $this->agreements = new ArrayCollection();
        $this->customers2Users = new ArrayCollection();
    }

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

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getStreetNumber(): ?string
    {
        return $this->street_number;
    }

    public function setStreetNumber(?string $street_number): self
    {
        $this->street_number = $street_number;

        return $this;
    }

    public function getApartmentNumber(): ?string
    {
        return $this->apartment_number;
    }

    public function setApartmentNumber(?string $apartment_number): self
    {
        $this->apartment_number = $apartment_number;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postal_code;
    }

    public function setPostalCode(?string $postal_code): self
    {
        $this->postal_code = $postal_code;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCreateDate(): ?DateTimeInterface
    {
        return $this->create_date;
    }

    public function setCreateDate(DateTimeInterface $create_date): self
    {
        $this->create_date = $create_date;

        return $this;
    }

    public function getUpdateDate(): ?DateTimeInterface
    {
        return $this->update_date;
    }

    public function setUpdateDate(DateTimeInterface $update_date): self
    {
        $this->update_date = $update_date;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return Collection|Agreement[]
     */
    public function getAgreements(): Collection
    {
        return $this->agreements;
    }

    public function addAgreement(Agreement $agreement): self
    {
        if (!$this->agreements->contains($agreement)) {
            $this->agreements[] = $agreement;
            $agreement->setCustomer($this);
        }

        return $this;
    }

    public function removeAgreement(Agreement $agreement): self
    {
        if ($this->agreements->contains($agreement)) {
            $this->agreements->removeElement($agreement);
            // set the owning side to null (unless already changed)
            if ($agreement->getCustomer() === $this) {
                $agreement->setCustomer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->customers2Users->map(function (Customers2Users $element) {
            return $element->getUser();
        });
    }

    public function addUser(User $user): self
    {
        if (!$this->customers2Users->exists(function ($key, Customers2Users $element) use ($user) {
            return $element->getUser()->getId() === $user->getId();
        })) {
            $c2u = new Customers2Users();
            $c2u->setUser($user);
            $c2u->setCustomer($this);
            $this->customers2Users->add($c2u);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->customers2Users->exists(function ($key, Customers2Users $element) use ($user) {
            return $element->getUser()->getId() === $user->getId();
        })) {
            $this->customers2Users->removeElement($user);
        }

        return $this;
    }
}
