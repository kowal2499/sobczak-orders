<?php

namespace App\Tests\End2End\Security;

use App\Entity\Grant;
use App\Entity\Module;
use App\Entity\Role;
use App\Repository\Authorization\GrantRepository;
use App\Repository\Authorization\ModuleRepository;
use App\Repository\RoleRepository;
use App\System\Test\ApiTestCase;
use App\ValueObject\Authorization\GrantType;

class GrantsResolverTest extends ApiTestCase
{

    private RoleRepository $roleRepository;
    private ModuleRepository $moduleRepository;
    private GrantRepository $grantRepository;

    protected function setUp(): void
    {
        $this->roleRepository = $this->getContainer()->get(RoleRepository::class);
        $this->moduleRepository = $this->getContainer()->get(ModuleRepository::class);
        $this->grantRepository = $this->getContainer()->get(GrantRepository::class);

        $this->createRole('ROLE_USER');
        $this->createRole('ROLE_CUSTOMER');
        $this->createRole('ROLE_PRODUCTION');
        $this->createModule('customers');
        $this->createModule('orders');
        $moduleProduction = $this->createModule('production');
        $this->createModule('configuration');

        $grantOrderPanel = $this->createGrant('production.order_panel', 'Panel produkcyjny', 'Określa dostęp do panelu produkcji', $moduleProduction, GrantType::Boolean);
    }

    public function testShouldPass(): void
    {
        $this->assertEquals(true, true);
    }

    private function createModule(string $name): Module
    {
        $module = $this->moduleRepository->findOneByNamespace($name);
        if (!$module) {
            $module = new Module();
            $module->setNamespace($name);
            $this->moduleRepository->save($module);
        }
        return $module;
    }

    private function createRole(string $name): Role
    {
        $role = $this->roleRepository->findOneByName($name);
        if (!$role) {
            $role = new Role();
            $role->setName($name);
            $this->roleRepository->save($role);
        }
        return $role;
    }

    private function createGrant(string $slug, string $name, string $description, Module $module, GrantType $type): Grant
    {
        $grant = $this->grantRepository->findOneBySlug($slug);
        if ($grant) {
            return $grant;
        }
        $grant = new Grant();
        $grant->setSlug($slug);
        $grant->setName($name);
        $grant->setDescription($description);
        $grant->setModule($module);
        $grant->setType($type);

        $this->grantRepository->save($grant);
        return $grant;
    }

}