<?php

namespace App\Tests\End2End\Modules\Authorization;

use App\System\Test\ApiTestCase;
use App\Utilities\Test\AuthHelper;

class GrantsResolverTest extends ApiTestCase
{
    private AuthHelper $authHelper;

    public function testShouldGetUserGrants(): void
    {
        // given
        $this->getAuthHelper()->createRole('ROLE_PRODUCTION', ['grant01=false', 'grant02=true', 'grant03']);
        $user = $this->createUser([], ['ROLE_PRODUCTION'], ['grant04=true', 'grant05:option01=true']);

        $client = $this->login($user);

        // when
        $client->xmlHttpRequest('GET', '/authorization/grants');

        // Then
        $content = json_decode($client->getResponse()->getContent(), true)['data'];

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        dd($content);
        $this->assertCount(4, $content);
        $this->assertSame(['grant02', 'grant03', 'grant04', 'grant05:option01'], $content);
    }
}