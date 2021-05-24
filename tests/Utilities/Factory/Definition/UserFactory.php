<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory\Definition;

use App\Entity\User;
use App\Tests\Utilities\Factory\FactoryDefinitionInterface;
use Faker\Generator;


class UserFactory implements FactoryDefinitionInterface
{
    const PASSWORD = '9r8D;PLKrEC..i,Lp7tFsArB';

    /**
     * @param Generator $faker
     * @return User
     */
    public function define(Generator $faker, callable $callback = null): User
    {
        $user = new User();
        $user->setEmail($faker->email);
        $user->setFirstName($faker->firstNameMale);
        $user->setLastName($faker->lastName);
        $user->setRoles(array_merge($user->getRoles(), ['ROLE_USER']));
        $user->setPassword(self::PASSWORD);

        return $user;
    }

    public static function supports(): string
    {
        return User::class;
    }
}