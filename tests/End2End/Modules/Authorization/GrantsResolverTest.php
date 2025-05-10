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
            'grant01',
            'Some name',
            'Some desc',
            $moduleProduction,
            GrantType::Boolean
        );
        $this->authFactory->createGrant(
            'grant02',
            'Some other name',
            'Some other desc',
            $moduleProduction,
            GrantType::Boolean
        );
        $this->authFactory->createGrant(
            'grant03',
            'Some other other name',
            'Some other other desc',
            $moduleProduction,
            GrantType::Select,
            new GrantOptionsCollection(
                new GrantOption('Option01', 'option01'),
                new GrantOption('Option02', 'option02'),
                new GrantOption('Option03', 'option03'),
            )
        );
        $this->authFactory->createRoleGrantValue(
            $roleProduction,
            new GrantValue(GrantVO::m('grant01'))
        );
        $this->authFactory->createRoleGrantValue(
            $roleProduction,
            new GrantValue(GrantVO::m('grant02'))
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
                new GrantValue(GrantVO::m('grant03:option01'), true),
                new GrantValue(GrantVO::m('grant03:option02'), true),
                new GrantValue(GrantVO::m('grant03:option03'), true),
            ]
        );
        $client = $this->login($user);

        // when
        $client->xmlHttpRequest('GET', '/authorization/grants');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);

        dd($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
//        dd($content);
        $this->assertEquals(['grant01', 'grant02', 'grant03.option01', 'grant03.option02', 'grant03.option03' ], $content);
    }
}