<?php

namespace App\Tests\End2End\Modules\UserSetting;

use App\Module\UserSetting\Entity\UserSetting;
use App\System\Test\ApiTestCase;

class UserSettingControllerTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    private function getUserSettingRepository()
    {
        return $this->getManager()->getRepository(UserSetting::class);
    }

    public function testShouldReturnNullWhenNoSettingSaved(): void
    {
        $user = $this->createUser();
        $client = $this->login($user);

        $client->request('GET', '/user-settings/dashboard.layout');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertNull($response['data']);
    }

    public function testShouldSaveAndRetrieveSetting(): void
    {
        $user = $this->createUser();
        $client = $this->login($user);

        $layout = ['widgets' => [['key' => 'working_days', 'x' => 0, 'y' => 0, 'w' => 4, 'h' => 2, 'visible' => true]]];

        $client->request('PUT', '/user-settings/dashboard.layout', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'data' => $layout,
        ]));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $client->request('GET', '/user-settings/dashboard.layout');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($layout, $response['data']);
    }

    public function testShouldOverwriteExistingSettingInsteadOfDuplicating(): void
    {
        $user = $this->createUser();
        $client = $this->login($user);

        $client->request('PUT', '/user-settings/dashboard.layout', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'data' => ['widgets' => [['key' => 'working_days']]],
        ]));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $client->request('PUT', '/user-settings/dashboard.layout', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'data' => ['widgets' => [['key' => 'factors_limit']]],
        ]));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $settings = $this->getUserSettingRepository()->findBy(['user' => $user, 'context' => 'dashboard.layout']);
        $this->assertCount(1, $settings);
        $this->assertEquals(['widgets' => [['key' => 'factors_limit']]], $settings[0]->getData());
    }

    public function testShouldKeepSettingsSeparatePerContext(): void
    {
        $user = $this->createUser();
        $client = $this->login($user);

        $client->request('PUT', '/user-settings/dashboard.layout', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'data' => ['widgets' => []],
        ]));
        $client->request('PUT', '/user-settings/other.context', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'data' => ['foo' => 'bar'],
        ]));

        $this->getManager()->clear();
        $settings = $this->getUserSettingRepository()->findBy(['user' => $user]);
        $this->assertCount(2, $settings);
    }
}
