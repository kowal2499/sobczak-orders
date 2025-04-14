<?php

namespace App\Tests\Utilities\Factory;

use App\Entity\Module;
use App\Entity\User;
use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Entity\AuthUserRole;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use App\Module\Authorization\Repository\AuthRoleRepository;
use App\Module\Authorization\Repository\AuthUserRoleRepository;
use App\Module\Authorization\ValueObject\GrantType;
use App\Module\Authorization\ValueObject\GrantValue;
use App\Repository\Authorization\ModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;

class AuthFactory
{
    private readonly Generator $faker;
    private readonly AuthRoleRepository $roleRepository;
    private readonly AuthUserRoleRepository $userRoleRepository;
    private readonly ModuleRepository $moduleRepository;
    private readonly AuthRoleGrantValueRepository $roleGrantValueRepository;
    private readonly AuthGrantRepository $grantRepository;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        $this->userRoleRepository = $em->getRepository(AuthUserRole::class);
        $this->roleRepository = $em->getRepository(AuthRole::class);
        $this->moduleRepository = $em->getRepository(Module::class);
        $this->roleGrantValueRepository = $em->getRepository(AuthRoleGrantValue::class);
        $this->grantRepository = $em->getRepository(AuthGrant::class);

        $this->faker = Factory::create('pl_PL');
    }

    public function createUser(
        array $data = [],
        array $roleNames = [],
        array $grants = [],
        bool $flush = true,
    ): User
    {
        $user = new User();
        $user->setEmail($data['email'] ?? $this->faker->email);
        $user->setPassword($data['password'] ?? $this->faker->password);
        $user->setFirstName($data['firstName'] ?? $this->faker->firstNameMale);
        $user->setLastName($data['lastName'] ?? $this->faker->lastName);

        $this->em->persist($user);

        // add roles
        foreach ($roleNames as $roleName) {
            $role = $this->createRole($roleName);
            $this->userRoleRepository->save(
                new AuthUserRole($user, $role),
                false
            );
        }

        if ($flush) {
            $this->em->flush();
        }

        return $user;
    }

    public function createRole(string $name): AuthRole
    {
        $role = $this->roleRepository->findOneByName($name);
        if (!$role) {
            $role = new AuthRole();
            $role->setName($name);
            $this->roleRepository->save($role);
        }
        return $role;
    }

    public function createModule(string $name): Module
    {
        $module = $this->moduleRepository->findOneByNamespace($name);
        if (!$module) {
            $module = new Module();
            $module->setNamespace($name);
            $this->moduleRepository->save($module);
        }
        return $module;
    }

    public function createRoleGrantValue(AuthRole $role, AuthGrant $grant, GrantValue $grantValue): AuthRoleGrantValue
    {
        $roleGrantValue = $this->roleGrantValueRepository->findOneByRoleAndGrant($role, $grant);
        if (!$roleGrantValue) {
            $roleGrantValue = new AuthRoleGrantValue($role, $grant, $grantValue);
            $this->roleGrantValueRepository->save($roleGrantValue);
        }
        return $roleGrantValue;
    }

    public function createGrant(string $slug, string $name, string $description, Module $module, GrantType $type, $options = null): AuthGrant
    {
        $grant = $this->grantRepository->findOneBySlug($slug);
        if ($grant) {
            return $grant;
        }
        $grant = new AuthGrant();
        $grant->setSlug($slug);
        $grant->setName($name);
        $grant->setDescription($description);
        $grant->setModule($module);
        $grant->setType($type);
        $grant->setOptions($options);

        $this->grantRepository->save($grant);
        return $grant;
    }
}