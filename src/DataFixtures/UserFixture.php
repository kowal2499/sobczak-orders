<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixture extends BaseFixture
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $userPasswordEncoder;

    public function __construct(UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(User::class, 5, function(User $user, $count) {
            $user
                ->setFirstName($this->faker->firstName())
                ->setLastName($this->faker->lastName())
                ->setEmail(sprintf('user%d@sobczak.com.pl', $count+1))
                ->setPassword($this->userPasswordEncoder->encodePassword(
                    $user,
                    'letmein'
                ))
            ;
        }, 'main_users');

        $this->createMany(User::class, 3, function(User $user, $count) {
            $user
                ->setFirstName($this->faker->firstName())
                ->setLastName($this->faker->lastName())
                ->setEmail(sprintf('admin%d@sobczak.com.pl', $count+1))
                ->setRoles(['ROLE_ADMIN'])
                ->setPassword($this->userPasswordEncoder->encodePassword(
                    $user,
                    'letmeinplease'
                ))
            ;
        }, 'admin_users');

        $manager->flush();
    }

}
