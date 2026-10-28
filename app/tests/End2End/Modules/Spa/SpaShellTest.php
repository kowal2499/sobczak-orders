<?php

namespace App\Tests\End2End\Modules\Spa;

use App\System\Test\ApiTestCase;

class SpaShellTest extends ApiTestCase
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

    public function testShouldServeShellForUnknownPathRequestedByBrowser(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // When
        $client->request('GET', '/some/front/route/123', [], [], ['HTTP_ACCEPT' => 'text/html,application/xhtml+xml']);

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('<div id="app"></div>', $client->getResponse()->getContent());
    }

    public function testShouldServeShellForRootPath(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // When
        $client->request('GET', '/', [], [], ['HTTP_ACCEPT' => 'text/html']);

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('<div id="app"></div>', $client->getResponse()->getContent());
    }

    /**
     * Former Symfony view routes are now SPA routes; a reload must still land on the shell.
     */
    public function testShouldServeShellForFormerViewRoutes(): void
    {
        // Given
        $client = $this->login($this->createUser());

        $paths = [
            '/orders', '/orders/5', '/orders/add', '/production', '/users', '/users/edit/1', '/work-configuration',
            '/bonus-settlement', '/products', '/products/new', '/products/edit/1', '/customers', '/customers/new',
            '/customers/edit/1',
        ];

        foreach ($paths as $path) {
            // When
            $client->request('GET', $path, [], [], ['HTTP_ACCEPT' => 'text/html']);

            // Then
            $this->assertSame(200, $client->getResponse()->getStatusCode(), $path);
            $this->assertStringContainsString('<div id="app"></div>', $client->getResponse()->getContent(), $path);
        }
    }

    public function testShouldReturnNotFoundForUnknownPathRequestedByXhr(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // When
        $client->request('GET', '/some/front/route/123', [], [], ['HTTP_ACCEPT' => 'application/json, text/plain, */*']);

        // Then
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testShouldNotShadowExistingApiRoutes(): void
    {
        // Given
        $client = $this->login($this->createUser());

        // When
        $client->request('GET', '/me', [], [], ['HTTP_ACCEPT' => 'text/html,application/xhtml+xml']);

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testShouldRedirectAnonymousUserToLogin(): void
    {
        // Given
        $client = static::getContainer()->get('test.client');

        // When
        $client->request('GET', '/some/front/route', [], [], ['HTTP_ACCEPT' => 'text/html']);

        // Then
        $this->assertTrue($client->getResponse()->isRedirect());
        $this->assertStringContainsString('/login', $client->getResponse()->headers->get('Location'));
    }
}
