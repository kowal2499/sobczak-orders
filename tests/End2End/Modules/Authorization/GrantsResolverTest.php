<?php

namespace App\Tests\End2End\Modules\Authorization;

use App\Entity\Module;
use App\Entity\User;
use App\Module\Authorization\Entity\AuthGrant;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use App\Module\Authorization\Repository\AuthRoleRepository;
use App\Module\Authorization\ValueObject\GrantOption;
use App\Module\Authorization\ValueObject\GrantOptionsCollection;
use App\Module\Authorization\ValueObject\GrantType;
use App\Module\Authorization\ValueObject\GrantValue;
use App\Repository\Authorization\ModuleRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;

class GrantsResolverTest extends ApiTestCase
{
    private AuthRoleRepository $roleRepository;
    private ModuleRepository $moduleRepository;
    private AuthGrantRepository $grantRepository;
    private AuthRoleGrantValueRepository $authRoleGrantValueRepository;
    private EntityFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new EntityFactory($this->getManager());

        $this->roleRepository = $this->getContainer()->get(AuthRoleRepository::class);
        $this->moduleRepository = $this->getContainer()->get(ModuleRepository::class);
        $this->grantRepository = $this->getContainer()->get(AuthGrantRepository::class);
        $this->authRoleGrantValueRepository = $this->getContainer()->get(AuthRoleGrantValueRepository::class);

        $this->createRole('ROLE_USER');
        $this->createRole('ROLE_CUSTOMER');
        $roleProduction = $this->createRole('ROLE_PRODUCTION');
        $this->createModule('customers');
        $this->createModule('orders');
        $moduleProduction = $this->createModule('production');
        $this->createModule('configuration');
        $this->user = $this->factory->make(User::class, ['roles' => ['ROLE_ADMIN']]);

        $grantProductionPanel = $this->createGrant(
            'production.panel',
            'Panel produkcji',
            'Określa dostęp do panelu produkcji',
            $moduleProduction,
            GrantType::Boolean
        );
        $grantProductionListFieldsView = $this->createGrant(
            'production.list.fields.read',
            'Kolumny w widoku listy produkcji',
            'Określa które pola (kolumny) są widoczne na liście produkcji',
            $moduleProduction,
            GrantType::Select,
            new GrantOptionsCollection(
                new GrantOption('Współczynnik', 'factor'),
                new GrantOption('Klient', 'customerId'),
                new GrantOption('Autor zamówienia', 'userId'),
                new GrantOption('Numer zamówienia', 'orderNumber'),
                new GrantOption('Produkt', 'productId'),
                new GrantOption('Data wystawienia', 'createDate'),
                new GrantOption('Data dostawy', 'confirmedDate'),
                new GrantOption('Data rozpoczęcia produkcji', 'productionStartDate'),
                new GrantOption('Data zakończenia produkcji', 'productionCompleteDate'),
            )
        );
        $this->createRoleGrantValue($roleProduction, $grantProductionPanel, new GrantValue(true));
        $this->createRoleGrantValue($roleProduction, $grantProductionListFieldsView, new GrantValue([
            'factor', 'customerId', 'userId', 'orderNumber', 'productId', 'createDate'
        ]));
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

    public function createRoleGrantValue(AuthRole $role, AuthGrant $grant, GrantValue $grantValue): AuthRoleGrantValue
    {
        $roleGrantValue = $this->authRoleGrantValueRepository->findOneByRoleAndGrant($role, $grant);
        if (!$roleGrantValue) {
            $roleGrantValue = new AuthRoleGrantValue($role, $grant, $grantValue);
            $this->authRoleGrantValueRepository->save($roleGrantValue);
        }
        return $roleGrantValue;
    }

    private function createGrant(string $slug, string $name, string $description, Module $module, GrantType $type, $options = null): AuthGrant
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