<?php

namespace App\Tests\End2End\Modules\ApiToken;

use App\Module\ApiToken\Repository\ApiTokenRepository;
use App\Module\ApiToken\Service\ApiTokenIssuer;
use App\System\Test\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ApiTokenCommandsTest extends ApiTestCase
{
    private ApiTokenRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->repository = $this->get(ApiTokenRepository::class);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldIssueTokenWithDefaultTtl(): void
    {
        // Given
        $user = $this->createUser(['email' => 'token-owner@example.com']);
        $this->getManager()->flush();

        // When
        $tester = $this->runCommand('app:api-token:create', [
            'email' => 'token-owner@example.com',
            '--name' => 'Postman - laptop',
        ]);

        // Then
        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());

        $tokens = $this->repository->findByUser($user);
        $this->assertCount(1, $tokens);
        $token = $tokens[0];

        $this->assertSame('Postman - laptop', $token->getName());
        $this->assertSame(
            (new \DateTimeImmutable(sprintf('+%d days', ApiTokenIssuer::DEFAULT_TTL_DAYS)))->format('Y-m-d'),
            $token->getExpiresAt()?->format('Y-m-d')
        );

        // And - the plaintext is printed once, and only its hash is what the lookup matches on
        $plainToken = $this->extractToken($tester->getDisplay());
        $found = $this->repository->findOneByHash(ApiTokenIssuer::hash($plainToken));
        $this->assertSame($token->getId(), $found?->getId());
    }

    public function testShouldIssueNonExpiringTokenForZeroTtl(): void
    {
        // Given
        $user = $this->createUser(['email' => 'forever@example.com']);
        $this->getManager()->flush();

        // When
        $tester = $this->runCommand('app:api-token:create', ['email' => 'forever@example.com', '--ttl' => '0']);

        // Then
        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertNull($this->repository->findByUser($user)[0]->getExpiresAt());
    }

    public function testShouldFailForUnknownEmail(): void
    {
        // When
        $tester = $this->runCommand('app:api-token:create', ['email' => 'nobody@example.com']);

        // Then
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Nie ma użytkownika', $tester->getDisplay());
    }

    public function testShouldFailForInactiveUser(): void
    {
        // Given
        $user = $this->createUser(['email' => 'inactive@example.com']);
        $user->setActive(false);
        $this->getManager()->flush();

        // When
        $tester = $this->runCommand('app:api-token:create', ['email' => 'inactive@example.com']);

        // Then
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('nieaktywne', $tester->getDisplay());
        $this->assertCount(0, $this->repository->findByUser($user));
    }

    public function testShouldListAndRevokeToken(): void
    {
        // Given
        $user = $this->createUser(['email' => 'listing@example.com']);
        $this->getManager()->flush();
        $this->runCommand('app:api-token:create', ['email' => 'listing@example.com', '--name' => 'Bruno']);
        $token = $this->repository->findByUser($user)[0];
        $tokenId = $token->getId();

        // When
        $listing = $this->runCommand('app:api-token:list', ['email' => 'listing@example.com']);

        // Then - the listing identifies the token without disclosing any part of it
        $this->assertSame(Command::SUCCESS, $listing->getStatusCode());
        $this->assertStringContainsString('Bruno', $listing->getDisplay());
        $this->assertStringNotContainsString(ApiTokenIssuer::TOKEN_PREFIX, $listing->getDisplay());

        // When - revoking removes it
        $revoke = $this->runCommand('app:api-token:revoke', ['id' => (string) $tokenId]);

        // Then
        $this->assertSame(Command::SUCCESS, $revoke->getStatusCode());
        $this->getManager()->clear();
        $this->assertNull($this->repository->find($tokenId));
    }

    public function testShouldFailRevokingUnknownToken(): void
    {
        // When
        $tester = $this->runCommand('app:api-token:revoke', ['id' => '999999']);

        // Then
        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    /**
     * @param array<string, string> $input
     */
    private function runCommand(string $name, array $input = []): CommandTester
    {
        $this->getManager(); // ensure the kernel is booted
        $application = new Application(self::$kernel);
        $tester = new CommandTester($application->find($name));
        $tester->execute($input);

        return $tester;
    }

    private function extractToken(string $display): string
    {
        preg_match('/(' . preg_quote(ApiTokenIssuer::TOKEN_PREFIX, '/') . '[0-9a-f]{64})/', $display, $matches);
        $this->assertNotEmpty($matches, 'Komenda nie wypisała jawnego tokenu.');

        return $matches[1];
    }
}
