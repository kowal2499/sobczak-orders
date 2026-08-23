<?php

namespace App\Tests\End2End\Modules\BonusSettlement;

use App\System\Test\ApiTestCase;

class ViewControllerTest extends ApiTestCase
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

    public function testShouldRenderViewForUserWithViewGrant(): void
    {
        // Given
        $user = $this->createUser([], [], ['bonus-settlement.view']);
        $client = $this->login($user);

        // When
        $client->request('GET', '/bonus-settlement');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('<bonus-settlement>', $client->getResponse()->getContent());
    }

    public function testShouldShowMenuEntryOnlyWithViewGrant(): void
    {
        // Given
        $withGrant = $this->createUser([], [], ['bonus-settlement.view']);
        $client = $this->login($withGrant);

        // When
        $client->request('GET', '/bonus-settlement');

        // Then
        $this->assertStringContainsString('Rozliczenie premii', $client->getResponse()->getContent());
    }

    public function testShouldKeepReportsGroupWithoutBonusEntryForOtherUsers(): void
    {
        // Given
        // ROLE_ADMIN to rola Symfony, więc czwarty argument - trzeci to granty modułowe
        $user = $this->createUser([], [], [], ['ROLE_ADMIN']);
        $client = $this->login($user);

        // When
        $client->request('GET', '/');

        // Then - grupa "Raporty" zostaje, ale bez pozycji premiowej
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Raport rozbieżności', $content);
        $this->assertStringNotContainsString('Rozliczenie premii', $content);
    }

    public function testShouldRejectUserWithoutViewGrant(): void
    {
        // Given
        $user = $this->createUser();
        $client = $this->login($user);

        // When
        $client->request('GET', '/bonus-settlement');

        // Then
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }
}
