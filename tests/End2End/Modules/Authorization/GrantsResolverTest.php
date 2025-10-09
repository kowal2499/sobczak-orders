<?php

namespace App\Tests\End2End\Modules\Authorization;

use App\System\Test\ApiTestCase;

class GrantsResolverTest extends ApiTestCase
{
    protected function setUp(): void
    {
        $this->getManager()->beginTransaction();
    }

    public function testShouldGetUserGrants(): void
    {
        // given
        $this->getAuthHelper()->createRole('ROLE_PRODUCTION4', ['grant02=true', 'grant03']);
        $user = $this->createUser([], ['ROLE_PRODUCTION4'], ['grant04=true', 'grant05:option01=true']);

        $client = $this->login($user);

        // when
        $client->xmlHttpRequest('GET', '/authorization/grants');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true)['data'];

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertCount(4, $content);
        $this->assertSame(['grant02', 'grant03', 'grant04', 'grant05:option01'], $content);
    }
}