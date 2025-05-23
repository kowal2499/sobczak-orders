<?php

namespace App\Tests\End2End\Modules\Authorization;

use App\Entity\User;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use App\Module\Authorization\Repository\AuthRoleRepository;
use App\Module\Authorization\Repository\AuthUserGrantValueRepository;
use App\Module\Authorization\Repository\AuthUserRoleRepository;
use App\Module\ModuleRegistry\Repository\ModuleRepository;
use App\Repository\UserRepository;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\AuthHelper;
use App\Tests\Utilities\Factory\AuthFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class GrantsResolverTest extends ApiTestCase
{
    private EntityFactory $factory;
    private AuthFactory $authFactory;
    private AuthHelper $authHelper;

    protected function setUp(): void
    {
        $this->factory = new EntityFactory($this->getManager());
        $this->authFactory = new AuthFactory($this->getManager());
        $this->authHelper = new AuthHelper(
            $this->getContainer()->get(AuthRoleRepository::class),
            $this->getContainer()->get(AuthGrantRepository::class),
            $this->getContainer()->get(AuthRoleGrantValueRepository::class),
            $this->getContainer()->get(AuthUserGrantValueRepository::class),
            $this->getContainer()->get(AuthUserRoleRepository::class),
            $this->getContainer()->get(ModuleRepository::class),
            $this->getContainer()->get(UserRepository::class),
        );
    }

    public function testShouldGetUserGrants(): void
    {
        // given
        $this->authHelper->createRole('ROLE_PRODUCTION', ['grant01=false', 'grant02=true', 'grant03']);
        $user = $this->authHelper->createUser(
            [],
            ['ROLE_PRODUCTION'],
            ['grant04=true']
//            ['grant03:option01=true', 'grant03:option02=true', 'grant03:option03=true']
        );

        $client = $this->login($user);

        // when
        $client->xmlHttpRequest('GET', '/authorization/grants');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(3, $content);
        $this->assertContains('grant01', $content);
        $this->assertContains('grant02', $content);
        $this->assertContains('grant04', $content);
    }
}