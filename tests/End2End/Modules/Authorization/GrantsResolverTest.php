<?php

namespace App\Tests\End2End\Modules\Authorization;

use App\Entity\User;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use App\Module\Authorization\Repository\AuthRoleRepository;
use App\Module\Authorization\Repository\AuthUserGrantValueRepository;
use App\Module\Authorization\Repository\AuthUserRoleRepository;
use App\Module\Authorization\ValueObject\GrantOption;
use App\Module\Authorization\ValueObject\GrantOptionsCollection;
use App\Module\Authorization\ValueObject\GrantType;
use App\Module\Authorization\ValueObject\GrantValue;
use App\Module\Authorization\ValueObject\GrantVO;
use App\Repository\Authorization\ModuleRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\EntityFactory;
use App\Tests\Utilities\Factory\AuthFactory;

class GrantsResolverTest extends ApiTestCase
{
    private EntityFactory $factory;
    private AuthFactory $authFactory;
    private AuthUserGrantValueRepository $userGrantValueRepository;

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

        $this->authFactory->createGrant(
            'production.panel',
            'Panel produkcji',
            'Określa dostęp do panelu produkcji',
            $moduleProduction,
            GrantType::Boolean
        );
        $this->authFactory->createGrant(
            'production.reports',
            'Raporty produkcji',
            'Określa dostęp do raportów produkcji',
            $moduleProduction,
            GrantType::Boolean
        );
        $this->authFactory->createGrant(
            'production.list',
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
        $this->authFactory->createRoleGrantValue(
            $roleProduction,
            new GrantValue(GrantVO::m('production.panel'))
        );
        $this->authFactory->createRoleGrantValue(
            $roleProduction,
            new GrantValue(GrantVO::m('production.reports'))
        );
        $this->authFactory->createRoleGrantValue(
            $roleProduction,
            new GrantValue(GrantVO::m('production.list:factor')),
            new GrantValue(GrantVO::m('production.list:customerId')),
            new GrantValue(GrantVO::m('production.list:userId'), true),
            new GrantValue(GrantVO::m('production.list:orderNumber')),
            new GrantValue(GrantVO::m('production.list:productId')),
            new GrantValue(GrantVO::m('production.list:createDate')),
        );
    }

    public function testShouldGetUserGrants(): void
    {
        // todo: mergowanie zgód

        // given
        $user = $this->authFactory->createUser(
            [],
            ['ROLE_PRODUCTION'],
            [
                new GrantValue(GrantVO::m('production.list:productionStartDate'), true),
                new GrantValue(GrantVO::m('production.list:productionCompleteDate'), true),
                new GrantValue(GrantVO::m('production.list:productId'), false),
                new GrantValue(GrantVO::m('production.reports'), false)
            ]
        );
        $client = $this->login($user);

        // when
        $client->xmlHttpRequest('GET', '/authorization/grants');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        dd($content);
        $this->assertEquals(['id' => $user->getId(), 'email' => $user->getEmail()], $content);
    }
}