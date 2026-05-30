<?php

namespace App\Tests\End2End\Modules\ActivityLog;

use App\Entity\User;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Module\ActivityLog\ValueObject\LogPriority;
use App\System\Test\ApiTestCase;

class CountLogsByFieldTest extends ApiTestCase
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

    public function testShouldAggregateLogCountByGivenField(): void
    {
        // Given
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $this->seedLog('agreement.created', 'l1', $user, ['customerId' => '10']);
        $this->seedLog('agreement.created', 'l2', $user, ['customerId' => '10']);
        $this->seedLog('agreement.created', 'l3', $user, ['customerId' => '20']);
        $this->seedLog('agreement.created', 'l4', $user, ['customerId' => '30']);
        // wrong type — should not be counted
        $this->seedLog('production.started', 'p1', $user, ['customerId' => '10']);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $client->request(
            'GET',
            '/log/agreement.created/count-by/customerId',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
        );

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $rows = json_decode($client->getResponse()->getContent(), true);

        $byValue = [];
        foreach ($rows as $row) {
            $byValue[$row['value']] = $row['count'];
        }
        ksort($byValue);

        $this->assertSame(['10' => 2, '20' => 1, '30' => 1], $byValue);
    }

    public function testShouldRespectAdditionalFieldFilters(): void
    {
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $this->seedLog('agreement.created', 'l1', $user, ['customerId' => '10', 'channel' => 'web']);
        $this->seedLog('agreement.created', 'l2', $user, ['customerId' => '10', 'channel' => 'web']);
        $this->seedLog('agreement.created', 'l3', $user, ['customerId' => '10', 'channel' => 'b2b']);
        $this->seedLog('agreement.created', 'l4', $user, ['customerId' => '20', 'channel' => 'web']);
        $this->getManager()->flush();
        $this->getManager()->clear();

        $client->request(
            'GET',
            '/log/agreement.created/count-by/customerId',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'fields' => [['name' => 'channel', 'value' => 'web']],
            ]),
        );

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $rows = json_decode($client->getResponse()->getContent(), true);

        $byValue = [];
        foreach ($rows as $row) {
            $byValue[$row['value']] = $row['count'];
        }
        ksort($byValue);

        $this->assertSame(['10' => 2, '20' => 1], $byValue);
    }

    public function testShouldReturn403WithoutGrant(): void
    {
        $user = $this->createUser();
        $client = $this->login($user);
        $this->getManager()->clear();

        $client->request('GET', '/log/agreement.created/count-by/customerId');

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @param array<string, string> $fields
     */
    private function seedLog(string $type, string $content, User $user, array $fields): ActivityLog
    {
        $log = new ActivityLog($type, $content, $user, LogLevel::INFO, LogPriority::normal);
        foreach ($fields as $name => $value) {
            $log->addLogField((string) $name, (string) $value);
        }
        $this->activityLogRepository->save($log, true);
        return $log;
    }
}
