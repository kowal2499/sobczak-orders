<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends BaseFixture
{
    private $passwordEncoder;
    private $adminPass;

    /**
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param $fixtureAdminPassword
     */
    public function __construct(UserPasswordEncoderInterface $passwordEncoder, $fixtureAdminPassword)
    {
        $this->passwordEncoder = $passwordEncoder;
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
                $this->passwordEncoder->encodePassword($user, $this->adminPass)
            )
            ->setRoles(['ROLE_ADMIN']);

        $manager->persist($user);
        $manager->flush();
        $this->setReference(self::REF_USER, $user);
    }
}