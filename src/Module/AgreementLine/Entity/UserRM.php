<?php

namespace App\Module\AgreementLine\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class UserRM
{
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    /**
     * @param int $id
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     */
    public function __construct(
        int $id,
        string $firstName,
        string $lastName,
        string $email
    ) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}