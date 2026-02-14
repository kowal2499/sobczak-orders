<?php

namespace App\Entity;

use App\Repository\Customers2UsersRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Table(name: 'user_customer')]
#[ORM\Entity(repositoryClass: Customers2UsersRepository::class)]
class Customers2Users
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'customers2Users')]
    private $user;

    #[Groups(['public_attr', '_main'])]
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: Customer::class, inversedBy: 'customers2Users')]
    private $customer;

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }
}
