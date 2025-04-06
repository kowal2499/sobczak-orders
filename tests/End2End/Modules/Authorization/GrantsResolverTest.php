<?php

namespace App\Tests\End2End\Modules\Authorization;

use App\Entity\Module;
use App\Entity\User;
use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthRoleRepository;
use App\Module\Authorization\ValueObject\GrantType;
use App\Repository\Authorization\ModuleRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class GrantsResolverTest extends ApiTestCase
{
    private AuthRoleRepository $roleRepository;
    private ModuleRepository $moduleRepository;
    private AuthGrantRepository $grantRepository;
    private EntityFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new EntityFactory($this->getManager());

        $this->roleRepository = $this->getContainer()->get(AuthRoleRepository::class);
        $this->moduleRepository = $this->getContainer()->get(ModuleRepository::class);
        $this->grantRepository = $this->getContainer()->get(AuthGrantRepository::class);

        $this->createRole('ROLE_USER');
        $this->createRole('ROLE_CUSTOMER');
        $this->createRole('ROLE_PRODUCTION');
        $this->createModule('customers');
        $this->createModule('orders');
        $moduleProduction = $this->createModule('production');
        $this->createModule('configuration');
        $this->user = $this->factory->make(User::class, ['roles' => ['ROLE_ADMIN']]);

        $grantOrderPanel = $this->createGrant('production.order_panel', 'Panel produkcyjny', 'Określa dostęp do panelu produkcji', $moduleProduction, GrantType::Boolean);
    }

    public function testShouldGetUserGrants(): void
    {
        // given
        $user = $this->factory->make(User::class, ['roles' => ['ROLE_USER']]);
        $this->getManager()->flush();
        $client = $this->login($user);

        // when
        $client->xmlHttpRequest('GET', '/authorization/grants');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(['id' => $user->getId(), 'email' => $user->getEmail()], $content);
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

    private function createRole(string $name): AuthRole
    {
        $role = $this->roleRepository->findOneByName($name);
        if (!$role) {
            $role = new AuthRole();
            $role->setName($name);
            $this->roleRepository->save($role);
        }
        return $role;
    }

    private function createGrant(string $slug, string $name, string $description, Module $module, GrantType $type): AuthGrant
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

        $this->grantRepository->save($grant);
        return $grant;
    }
}