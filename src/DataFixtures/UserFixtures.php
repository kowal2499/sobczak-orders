<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends BaseFixture
{
    private $passwordHasher;
    private $adminPass;

    /**
     * @param UserPasswordHasherInterface $passwordHasher
     * @param $fixtureAdminPassword
     */
    public function __construct(UserPasswordHasherInterface $passwordHasher, $fixtureAdminPassword)
    {
        $this->passwordHasher = $passwordHasher;
        $this->adminPass = $fixtureAdminPassword;
    }

    protected function loadData(ObjectManager $manager)
    {
        $user = new User();
        $user
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('test@test.pl')
            ->setPassword(
                $this->passwordHasher->hashPassword($user, $this->adminPass)
            )
            ->setRoles(['ROLE_ADMIN']);

        $manager->persist($user);
        $manager->flush();
        $this->setReference(self::REF_USER, $user);
    }
}