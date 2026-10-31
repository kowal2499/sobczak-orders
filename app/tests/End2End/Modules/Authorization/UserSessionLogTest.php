<?php

namespace App\Tests\End2End\Modules\Authorization;

use App\Entity\User;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\ApiToken\Service\ApiTokenIssuer;
use App\Module\Authorization\EventSubscriber\UserSessionLogSubscriber;
use App\System\Test\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserSessionLogTest extends ApiTestCase
{
    private const PASSWORD = 'secret-password';

    private KernelBrowser $client;
    private ActivityLogRepository $logRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->getManager()->beginTransaction();
        $this->logRepository = $this->get(ActivityLogRepository::class);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldJournalFormLogin(): void
    {
        // Given
        $user = $this->createUserWithPassword();

        // When
        $this->submitLoginForm($user);

        // Then
        $this->assertTrue($this->client->getResponse()->isRedirect());
        $logs = $this->logsOf(UserSessionLogSubscriber::TYPE_LOGIN, $user);
        $this->assertCount(1, $logs);
        $this->assertSame('activity_log.user.logged_in', $logs[0]->getContent());
        $this->assertSame(['userId' => (string) $user->getId()], $this->fieldsOf($logs[0]));
    }

    public function testShouldNotJournalFailedLogin(): void
    {
        // Given
        $user = $this->createUserWithPassword();

        // When
        $this->submitLoginForm($user, 'wrong-password');

        // Then
        $this->assertCount(0, $this->logsOf(UserSessionLogSubscriber::TYPE_LOGIN, $user));
    }

    public function testShouldJournalLogout(): void
    {
        // Given
        $user = $this->createUserWithPassword();
        $this->submitLoginForm($user);

        // When
        $this->client->request('GET', '/logout');

        // Then
        $logs = $this->logsOf(UserSessionLogSubscriber::TYPE_LOGOUT, $user);
        $this->assertCount(1, $logs);
        $this->assertSame('activity_log.user.logged_out', $logs[0]->getContent());
        $this->assertSame(['userId' => (string) $user->getId()], $this->fieldsOf($logs[0]));
    }

    public function testShouldNotJournalSessionRestoredFromRememberMeCookie(): void
    {
        // Given - a signed-in user whose session expired, but whose remember-me cookie is still there
        $user = $this->createUserWithPassword();
        $this->submitLoginForm($user);
        $this->assertNotNull($this->client->getCookieJar()->get('REMEMBERME'));
        $this->client->getCookieJar()->expire('MOCKSESSID');

        // When
        $this->client->request('GET', '/me');

        // Then - the user is sent back to the login form, so only the original form login counts
        $this->assertResponseRedirects('/login');
        $this->assertCount(1, $this->logsOf(UserSessionLogSubscriber::TYPE_LOGIN, $user));
    }

    public function testShouldNotJournalApiTokenRequests(): void
    {
        // Given
        $user = $this->createUserWithPassword();
        $issued = $this->get(ApiTokenIssuer::class)->issue($user, 'Postman');

        // When
        $this->client->request('GET', '/me', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $issued->plainToken]);

        // Then
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertCount(0, $this->logsOf(UserSessionLogSubscriber::TYPE_LOGIN, $user));
    }

    private function createUserWithPassword(): User
    {
        $user = $this->createUser();
        $hasher = $this->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, self::PASSWORD));
        $this->getManager()->flush();

        return $user;
    }

    private function submitLoginForm(User $user, string $password = self::PASSWORD): void
    {
        $crawler = $this->client->request('GET', '/login');
        $this->client->request('POST', '/login', [
            'email' => $user->getEmail(),
            'password' => $password,
            '_csrf_token' => $crawler->filter('input[name="_csrf_token"]')->attr('value'),
        ]);
    }

    /**
     * @return ActivityLog[]
     */
    private function logsOf(string $type, User $user): array
    {
        $this->getManager()->clear();

        return $this->logRepository->findBy(['type' => $type, 'user' => $user->getId()]);
    }

    /**
     * @return array<string, string>
     */
    private function fieldsOf(ActivityLog $log): array
    {
        $fields = [];
        foreach ($log->getLogFields() as $field) {
            $fields[$field->getName()] = $field->getValue();
        }

        return $fields;
    }
}
