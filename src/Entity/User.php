<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    public function __construct()
    {
        $this->statusLogs = new ArrayCollection();
        $this->customers2Users = new ArrayCollection();
    }
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"user_main", "_linePanel"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups({"user_main", "_linePanel"})
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     * @Groups("user_main")
     */
    private $roles = [];

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("user_main")
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("user_main")
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\StatusLog", mappedBy="user")
     */
    private $statusLogs;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Customers2Users", mappedBy="user", cascade={"persist"}, fetch="EAGER", orphanRemoval=true)
     * @Groups("user_main")
     */
    private $customers2Users;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * Get user's full name string
     * @Groups({"user_main", "_linePanel"})
     * @return string
     */
    public function getUserFullName(): string
    {
        return implode(' ', [$this->getFirstName(), $this->getLastName()]);
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using bcrypt or argon
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return Collection|StatusLog[]
     */
    public function getStatusLogs(): Collection
    {
        return $this->statusLogs;
    }

    public function addStatusLog(StatusLog $statusLog): self
    {
        if (!$this->statusLogs->contains($statusLog)) {
            $this->statusLogs[] = $statusLog;
            $statusLog->setUser($this);
        }

        return $this;
    }

    public function removeStatusLog(StatusLog $statusLog): self
    {
        if ($this->statusLogs->contains($statusLog)) {
            $this->statusLogs->removeElement($statusLog);
            // set the owning side to null (unless already changed)
            if ($statusLog->getUser() === $this) {
                $statusLog->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Customer[]
     */
    public function getCustomers(): Collection
    {
        return $this->customers2Users->map(function (Customers2Users $element) {
            return $element->getCustomer();
        });
    }

    public function addCustomer(Customer $customer): self
    {
        if (!$this->customers2Users->exists(function ($key, Customers2Users $element) use ($customer) {
            return $element->getCustomer()->getId() === $customer->getId();
        })) {
            $c2u = new Customers2Users();
            $c2u->setUser($this);
            $c2u->setCustomer($customer);
            $this->customers2Users->add($c2u);
        }

        return $this;
    }

    public function removeCustomer(Customer $customer): self
    {
        if ($this->customers2Users->exists(function ($key, Customers2Users $element) use ($customer) {
            return $element->getCustomer()->getId() === $customer->getId();
        })) {
            $this->customers2Users->removeElement($customer);
        }

        return $this;
    }

    public function getCustomers2Users(): Collection
    {
        return $this->customers2Users;
    }

    public function addCustomers2User(Customers2Users $customers2Users): void
    {
        if (!$this->customers2Users->contains($customers2Users)) {
            $this->customers2Users[] = $customers2Users;
            $customers2Users->setUser($this);
        }
    }

    public function removeCustomers2User(Customers2Users $customer2user): void
    {
        if ($this->customers2Users->contains($customer2user)) {
            $this->customers2Users->removeElement($customer2user);
        }
    }
}
