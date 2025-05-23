<?php

namespace App\Tests\Utilities;

use App\Entity\User;
use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Entity\AuthUserGrantValue;
use App\Module\Authorization\Entity\AuthUserRole;
use App\Module\Authorization\Repository\Interface\AuthGrantRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthRoleGrantValueRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthRoleRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthUserGrantValueRepositoryInterface;
use App\Module\Authorization\Repository\Interface\AuthUserRoleRepositoryInterface;
use App\Module\Authorization\ValueObject\GrantType;
use App\Module\Authorization\ValueObject\GrantVO;
use App\Module\ModuleRegistry\Entity\Module;
use App\Module\ModuleRegistry\Repository\ModuleRepository;
use App\Repository\UserRepository;
use Faker\Factory;

class AuthHelper
{
    protected \Faker\Generator $faker;

    public function __construct(
        protected AuthRoleRepositoryInterface $roleRepository,
        protected AuthGrantRepositoryInterface $grantRepository,
        protected AuthRoleGrantValueRepositoryInterface $roleGrantValueRepository,
        protected AuthUserGrantValueRepositoryInterface $userGrantValueRepository,
        protected AuthUserRoleRepositoryInterface $roleUserRepository,
        protected ModuleRepository $moduleRepository,
        protected UserRepository $userRepository,
    ) {
        $this->faker = Factory::create('pl_PL');
    }


    public function createUser(array $data = [], array $roleNames = [], array $grantNames = []): User
    {
        $user = new User();
        $user->setEmail($data['email'] ?? $this->faker->email);
        $user->setFirstName($this->faker->firstNameFemale());
        $user->setLastName($this->faker->lastName());
        $user->setPassword($this->faker->password(10));
        $this->userRepository->add($user);

        foreach ($roleNames as $roleName) {
            $role = $this->createRole($roleName);
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

    public function createRole(string $name, array $grantNames = []): AuthRole
    {
        $role = $this->roleRepository->findOneByName($name);
        if (!$role) {
            $role = new AuthRole();
            $role->setName($name);
            $this->roleRepository->add($role);
        }

        foreach ($grantNames as $grant) {
            $grantVO = GrantVO::m($grant);
            $grantInstance = $this->grantRepository->findOneBySlug($grantVO->getSlug());
            if (!$grantInstance) {
                $grantInstance = $this->createGrant($grant);
            }
            $roleGrantValue = $this->roleGrantValueRepository->findOneByRoleAndGrant($role, $grantInstance, $grantVO->getOptionSlug());
            if (!$roleGrantValue) {
                $roleGrantValue = new AuthRoleGrantValue($role, $grantInstance, $grantVO->getOptionSlug());
            }
            $roleGrantValue->setValue($grantVO->getValue());
            $this->roleGrantValueRepository->add($roleGrantValue);
        }
        return $role;
    }

    public function createGrant(string $grant, ?Module $module = null): AuthGrant
    {
        $grantVO = GrantVO::m($grant);
        $grant = new AuthGrant();
        if (!$module) {
            $module = $this->moduleRepository->findOneByNamespace('testmodule');
            if (!$module) {
                $module = new Module();
                $module->setNamespace('testmodule');
                $this->moduleRepository->add($module);
            }
        }
        $grant->setModule($module);
        $grant->setName($this->faker->name);
        $grant->setType($grantVO->getOptionSlug() ? GrantType::Select : GrantType::Boolean);
//        PrivateProperty::setId($grant);
        $grant->setSlug($grantVO->getSlug());
        $this->grantRepository->add($grant);

        return $grant;
    }
}