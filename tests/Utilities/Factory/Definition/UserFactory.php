<?php
/** @author: Roman Kowalski */

namespace App\Tests\Utilities\Factory\Definition;

use App\Entity\User;
use App\Tests\Utilities\Factory\FactoryDefinitionInterface;
use Faker\Generator;

class UserFactory implements FactoryDefinitionInterface
{
    const PASSWORD = '9r8D;PLKrEC..i,Lp7tFsArB';

    public static function supports(): string
    {
        return User::class;
    }

    public function defaultProperties(Generator $faker): array
    {
        return [
            'email' => $faker->email,
            'firstName' => $faker->firstNameMale,
            'lastName' => $faker->lastName,
            'roles' => ['ROLE_USER'],
            'password' => self::PASSWORD
        ];
    }
}