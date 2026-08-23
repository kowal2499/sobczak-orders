<?php

namespace App\Tests\End2End\Modules\ApiToken;

use App\Entity\User;
use App\Module\ApiToken\Entity\ApiToken;
use App\Module\ApiToken\Repository\ApiTokenRepository;
use App\Module\ApiToken\Service\ApiTokenIssuer;
use App\System\Test\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ApiTokenAuthenticationTest extends ApiTestCase
{
    private const PROBE_ENDPOINT = '/user/grants';

    private KernelBrowser $client;
    private ApiTokenRepository $repository;
    private ApiTokenIssuer $issuer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        // Fixtures live in an uncommitted transaction; a kernel reboot would open a second
        // connection that cannot see them.
        $this->client->disableReboot();
        $this->getManager()->beginTransaction();
        $this->repository = $this->get(ApiTokenRepository::class);
        $this->issuer = $this->get(ApiTokenIssuer::class);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldAuthenticateWithValidToken(): void
    {
        // Given
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $this->getManager()->flush();
        $issued = $this->issuer->issue($user, 'Postman');

        // When
        $this->request($issued->plainToken);

        // Then
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        // And - it is the token owner who is logged in, with their own grants
        $grants = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertIsArray($grants);
        $this->assertContains('work-configuration.capacity', $grants);
    }

    public function testShouldRecordLastUsedAt(): void
    {
        // Given
        $user = $this->createUser();
        $this->getManager()->flush();
        $issued = $this->issuer->issue($user, 'Postman');
        $this->assertNull($issued->token->getLastUsedAt());
        $tokenId = $issued->token->getId();

        // When
        $this->request($issued->plainToken);

        // Then
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->getManager()->clear();
        $this->assertNotNull($this->repository->find($tokenId)?->getLastUsedAt());
    }

    public function testShouldRejectUnknownToken(): void
    {
        // Given - a syntactically valid token that was never issued
        $this->createUser();
        $this->getManager()->flush();

        // When
        $this->request(ApiTokenIssuer::TOKEN_PREFIX . str_repeat('a', 64));

        // Then
        $this->assertUnauthorized('Nieznany token API.');
    }

    public function testShouldRejectExpiredToken(): void
    {
        // Given
        $user = $this->createUser();
        $this->getManager()->flush();
        $plainToken = $this->persistToken($user, new \DateTimeImmutable('-1 day'));

        // When
        $this->request($plainToken);

        // Then
        $this->assertUnauthorized('Token API wygasł.');
    }

    public function testShouldRejectTokenOfInactiveUser(): void
    {
        // Given
        $user = $this->createUser();
        $user->setActive(false);
        $this->getManager()->flush();
        $plainToken = $this->persistToken($user, new \DateTimeImmutable('+90 days'));

        // When
        $this->request($plainToken);

        // Then
        $this->assertUnauthorized('Konto powiązane z tokenem jest nieaktywne.');
    }

    public function testShouldNotInterfereWithSessionLogin(): void
    {
        // Given - no Authorization header at all, the browser flow as before
        $user = $this->createUser([], [], ['work-configuration.capacity']);
        $this->getManager()->flush();

        // When
        $client = $this->login($user);
        $client->request('GET', self::PROBE_ENDPOINT);

        // Then
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    private function request(string $plainToken): void
    {
        $this->client->request('GET', self::PROBE_ENDPOINT, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $plainToken,
        ]);
    }

    private function assertUnauthorized(string $expectedMessage): void
    {
        $response = $this->client->getResponse();

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(
            ['message' => $expectedMessage],
            json_decode((string) $response->getContent(), true)
        );
    }

    /**
     * ApiTokenIssuer celowo nie pozwala wydać tokenu wygasłego, a takiego potrzebujemy w teście.
     */
    private function persistToken(User $user, \DateTimeImmutable $expiresAt): string
    {
        $plainToken = ApiTokenIssuer::TOKEN_PREFIX . bin2hex(random_bytes(32));

        $this->repository->save(new ApiToken(
            $user,
            'Test',
            ApiTokenIssuer::hash($plainToken),
            $expiresAt,
        ));

        return $plainToken;
    }
}
