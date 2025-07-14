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
            $grantInstance = $this->getOrCreateGrant($grantName);
            $val = new AuthUserGrantValue($user, $grantInstance, $grantVO->getOptionSlug());
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

        foreach ($grantNames as $grantName) {
            $grantVO = GrantVO::m($grantName);
            $grantInstance = $this->getOrCreateGrant($grantName);
            $roleGrantValue = $this->roleGrantValueRepository->findOneByRoleAndGrant($role, $grantInstance, $grantVO->getOptionSlug());
            if (!$roleGrantValue) {
                $roleGrantValue = new AuthRoleGrantValue($role, $grantInstance, $grantVO->getOptionSlug());
            }
            $roleGrantValue->setValue($grantVO->getValue());
            $this->roleGrantValueRepository->add($roleGrantValue);
        }
        return $role;
    }

    public function getOrCreateGrant(string $grant, ?Module $module = null): AuthGrant
    {
        $grantVO = GrantVO::m($grant);
        $grantInstance = $this->grantRepository->findOneBySlug($grantVO->getSlug());
        if (!$grantInstance) {
            if (!$module) {
                $module = $this->moduleRepository->findOneByNamespace('testmodule');
                if (!$module) {
                    $module = new Module();
                    $module->setNamespace('testmodule');
                    $this->moduleRepository->add($module);
                }
            }
            $grantInstance = new AuthGrant($grantVO->getSlug(), $module);
            $grantInstance->setName($this->faker->name);
            $grantInstance->setType($grantVO->getOptionSlug() ? GrantType::Select : GrantType::Boolean);
            $this->grantRepository->add($grantInstance);
        }
        return $grantInstance;
    }
}