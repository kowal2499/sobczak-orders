<?php

namespace App\Tests\End2End\Modules\Authorization;

use App\Entity\User;
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
use App\Tests\Utilities\Factory\AuthFactory;

class GrantsResolverTest extends ApiTestCase
{
    private EntityFactory $factory;
    private AuthFactory $authFactory;

    protected function setUp(): void
    {
        $this->factory = new EntityFactory($this->getManager());
        $this->authFactory = new AuthFactory($this->getManager());

        $this->roleRepository = $this->getContainer()->get(AuthRoleRepository::class);
        $this->moduleRepository = $this->getContainer()->get(ModuleRepository::class);
        $this->grantRepository = $this->getContainer()->get(AuthGrantRepository::class);
        $this->authRoleGrantValueRepository = $this->getContainer()->get(AuthRoleGrantValueRepository::class);

        $this->authFactory->createRole('ROLE_CUSTOMER');
        $roleProduction = $this->authFactory->createRole('ROLE_PRODUCTION');
        $this->authFactory->createModule('customers');
        $this->authFactory->createModule('orders');
        $moduleProduction = $this->authFactory->createModule('production');
        $this->authFactory->createModule('configuration');
        $this->user = $this->factory->make(User::class, ['roles' => ['ROLE_ADMIN']]);

        $grantProductionPanel = $this->authFactory->createGrant(
            'production.panel',
            'Panel produkcji',
            'Określa dostęp do panelu produkcji',
            $moduleProduction,
            GrantType::Boolean
        );
        $grantProductionListFieldsView = $this->authFactory->createGrant(
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
        $this->authFactory->createRoleGrantValue($roleProduction, $grantProductionPanel, new GrantValue(true));
        $this->authFactory->createRoleGrantValue($roleProduction, $grantProductionListFieldsView, new GrantValue([
            'factor', 'customerId', 'userId', 'orderNumber', 'productId', 'createDate'
        ]));
    }

    public function testShouldGetUserGrants(): void
    {
        // given
        $user = $this->authFactory->createUser([], ['ROLE_USER']);
//        $user = $this->factory->make(User::class, ['roles' => ['ROLE_USER']]);
        $client = $this->login($user);

        // when
        $client->xmlHttpRequest('GET', '/authorization/grants');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(['id' => $user->getId(), 'email' => $user->getEmail()], $content);
    }
}