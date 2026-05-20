<?php

namespace App\Tests\End2End\Modules\ActivityLog;

use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Module\ActivityLog\ValueObject\LogPriority;
use App\System\Test\ApiTestCase;

class AddActivityLogTest extends ApiTestCase
{
    private ActivityLogRepository $activityLogRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->activityLogRepository = $this->get(ActivityLogRepository::class);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldCreateLog(): void
    {
        // Given
        $user = $this->createUser([], [], ['activity-log.create']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->request(
            'POST',
            '/log/agreement.created',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'message' => 'Zamówienie #123 utworzone',
                'fields' => [
                    ['name' => 'agreementId', 'value' => '123'],
                    ['name' => 'customerId', 'value' => '42'],
                ],
                'priority' => LogPriority::high->value,
            ]),
        );

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $logs = $this->activityLogRepository->findBy(['type' => 'agreement.created']);
        $this->assertCount(1, $logs);

        $log = $logs[0];
        $this->assertEquals('agreement.created', $log->getType());
        $this->assertEquals('Zamówienie #123 utworzone', $log->getContent());
        $this->assertEquals(LogLevel::INFO, $log->getLevel());
        $this->assertEquals(LogPriority::high, $log->getPriority());
        $this->assertNotNull($log->getUser());
        $this->assertEquals($user->getId(), $log->getUser()->getId());

        $fields = $this->fieldsByName($log);
        $this->assertSame(['agreementId' => '123', 'customerId' => '42'], $fields);
    }

    public function testShouldKeepFirstValueWhenSameFieldNameRepeats(): void
    {
        // Given
        $user = $this->createUser([], [], ['activity-log.create']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->request(
            'POST',
            '/log/agreement.created',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'message' => 'duplicate field test',
                'fields' => [
                    ['name' => 'agreementId', 'value' => 'first'],
                    ['name' => 'agreementId', 'value' => 'second'],
                ],
            ]),
        );

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $log = $this->activityLogRepository->findOneBy(['type' => 'agreement.created']);
        $this->assertNotNull($log);

        $fields = $this->fieldsByName($log);
        $this->assertSame(['agreementId' => 'first'], $fields);
    }

    public function testShouldRejectMissingMessage(): void
    {
        $user = $this->createUser([], [], ['activity-log.create']);
        $client = $this->login($user);
        $this->getManager()->clear();

        $client->request(
            'POST',
            '/log/agreement.created',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['fields' => []]),
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403WithoutGrant(): void
    {
        $user = $this->createUser();
        $client = $this->login($user);
        $this->getManager()->clear();

        $client->request(
            'POST',
            '/log/agreement.created',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['message' => 'no grant']),
        );

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldImpersonateAuthorWhenImpersonateUserIdProvided(): void
    {
        // Given
        $author = $this->createUser([], [], ['activity-log.create']);
        $impersonated = $this->createUser(['email' => 'impersonated@example.com']);
        $client = $this->login($author);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request(
            'POST',
            '/log/agreement.created',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'message' => 'impersonated action',
                'fields' => [
                    ['name' => 'impersonateUserId', 'value' => (string) $impersonated->getId()],
                    ['name' => 'agreementId', 'value' => '7'],
                ],
            ]),
        );

        // Then
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $log = $this->activityLogRepository->findOneBy(['type' => 'agreement.created']);
        $this->assertNotNull($log);
        $this->assertNotNull($log->getUser());
        $this->assertEquals($impersonated->getId(), $log->getUser()->getId(), 'log author should be the impersonated user');

        $fields = $this->fieldsByName($log);
        $this->assertArrayNotHasKey('impersonateUserId', $fields, 'impersonateUserId must not be persisted as a log field');
        $this->assertSame('7', $fields['agreementId']);
    }

    public function testShouldFailWhenImpersonatedUserMissing(): void
    {
        $author = $this->createUser([], [], ['activity-log.create']);
        $client = $this->login($author);
        $this->getManager()->clear();

        $client->request(
            'POST',
            '/log/agreement.created',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'message' => 'bad impersonation',
                'fields' => [
                    ['name' => 'impersonateUserId', 'value' => '999999'],
                ],
            ]),
        );

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('Impersonated user', $response['error']);

        $this->getManager()->clear();
        $logs = $this->activityLogRepository->findAll();
        $this->assertCount(0, $logs, 'failed impersonation must not persist a log');
    }

    /**
     * @return array<string, string>
     */
    private function fieldsByName(ActivityLog $log): array
    {
        $out = [];
        foreach ($log->getLogFields() as $field) {
            $out[$field->getName()] = $field->getValue();
        }
        return $out;
    }
}
