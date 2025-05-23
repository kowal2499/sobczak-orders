<?php

namespace App\Tests\Unit\Modules\Authorization;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Entity\AuthUserGrantValue;
use App\Module\Authorization\Entity\AuthUserRole;
use App\Module\Authorization\Repository\Test\AuthGrantTestRepository;
use App\Module\Authorization\Repository\Test\AuthRoleGrantValueTestRepository;
use App\Module\Authorization\Repository\Test\AuthRoleTestRepository;
use App\Module\Authorization\Repository\Test\AuthUserGrantValueTestRepository;
use App\Module\Authorization\Repository\Test\AuthUserRoleTestRepository;
use App\Module\Authorization\ValueObject\GrantVO;
use App\Tests\Utilities\PrivateProperty;
use Faker\Factory;
use PHPUnit\Framework\TestCase;

abstract class AuthBase extends TestCase
{
    protected \Faker\Generator $faker;
    protected AuthRoleTestRepository $roleRepository;
    protected AuthGrantTestRepository $grantRepository;
    protected AuthRoleGrantValueTestRepository $roleGrantValueRepository;
    protected AuthUserGrantValueTestRepository $userGrantValueRepository;
    protected AuthUserRoleTestRepository $roleUserRepository;

    protected function init()
    {
        $this->faker = Factory::create('pl_PL');
        $this->roleRepository = new AuthRoleTestRepository();
        $this->grantRepository = new AuthGrantTestRepository();

        $this->roleGrantValueRepository = new AuthRoleGrantValueTestRepository();
        $this->userGrantValueRepository = new AuthUserGrantValueTestRepository();
        $this->roleUserRepository = new AuthUserRoleTestRepository();
    }

    protected function createUser(array $data = [], array $roleNames = [], array $grantNames = []): User
    {
        $user = new User();
        PrivateProperty::setId($user, $data['id'] ?? null);
        $user->setEmail($data['email'] ?? $this->faker->email);

        foreach ($roleNames as $roleName) {
            $role = $this->roleRepository->findOneByName($roleName);
            if (!$role) {
                $role = $this->createRole($roleName, []);
            }
            $this->roleUserRepository->add(new AuthUserRole($user, $role));
        }

        foreach ($grantNames as $grantName) {
            $grantVO = GrantVO::m($grantName);
            $grant = $this->grantRepository->findOneBySlug($grantVO->getSlug());
            if (!$grant) {
                $grant = $this->createGrant($grantName);
            }
            $val = new AuthUserGrantValue($user, $grant);
            $val->setValue($grantVO->getValue());
            $this->userGrantValueRepository->add($val);
        }

        return $user;
    }

    protected function createRole(string $name, array $grantNames): AuthRole
    {
        $role = new AuthRole();
        PrivateProperty::setId($role);
        $role->setName($name);
        $this->roleRepository->add($role);

        foreach ($grantNames as $grant) {
            $grantVO = GrantVO::m($grant);
            $grantInstance = $this->grantRepository->findOneBySlug($grantVO->getSlug());
            if (!$grantInstance) {
                $grantInstance = $this->createGrant($grant);
            }
            $roleGrantValue = new AuthRoleGrantValue($role, $grantInstance, $grantVO->getOptionSlug());
            $roleGrantValue->setValue($grantVO->getValue());
            $this->roleGrantValueRepository->add($roleGrantValue);
        }
        return $role;
    }

    protected function createGrant(string $grant): AuthGrant
    {
        $grantVO = GrantVO::m($grant);
        $grant = new AuthGrant();
        PrivateProperty::setId($grant);
        $grant->setSlug($grantVO->getSlug());
        $this->grantRepository->add($grant);

        return $grant;
    }
}