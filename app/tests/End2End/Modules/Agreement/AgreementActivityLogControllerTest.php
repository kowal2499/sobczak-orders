<?php

namespace App\Tests\End2End\Modules\Agreement;

use App\Entity\User;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Module\ActivityLog\ValueObject\LogPriority;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\System\Test\ApiTestCase;

class AgreementActivityLogControllerTest extends ApiTestCase
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

    public function testShouldReturnLogsForAgreementOrderedNewestFirst(): void
    {
        // Given
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $agreementId = 100;
        $line01Id = 201;
        $line02Id = 202;

        // Our agreement: 1 created + 2 line.created + 1 line.archived
        $this->seedLog(
            AgreementActivityLogType::AGREEMENT_CREATED->value,
            'activity_log.agreement.created',
            $user,
            ['agreementId' => (string) $agreementId],
            new \DateTime('2026-05-01 09:00:00'),
        );
        $this->seedLog(
            AgreementActivityLogType::AGREEMENT_LINE_CREATED->value,
            'activity_log.agreement_line.created',
            $user,
            ['id' => (string) $line01Id, 'agreementId' => (string) $agreementId],
            new \DateTime('2026-05-01 09:00:01'),
            ['productName' => 'Krzesło Beton'],
        );
        $this->seedLog(
            AgreementActivityLogType::AGREEMENT_LINE_CREATED->value,
            'activity_log.agreement_line.created',
            $user,
            ['id' => (string) $line02Id, 'agreementId' => (string) $agreementId],
            new \DateTime('2026-05-01 09:00:02'),
            ['productName' => 'Stół Dębowy'],
        );
        $this->seedLog(
            AgreementActivityLogType::AGREEMENT_LINE_ARCHIVED->value,
            'activity_log.agreement_line.archived',
            $user,
            ['id' => (string) $line01Id, 'agreementId' => (string) $agreementId],
            new \DateTime('2026-05-02 12:00:00'),
            ['productName' => 'Krzesło Beton'],
        );

        // Noise: unrelated agreement
        $this->seedLog(
            AgreementActivityLogType::AGREEMENT_CREATED->value,
            'activity_log.agreement.created',
            $user,
            ['agreementId' => '9999'],
            new \DateTime('2026-05-03 08:00:00'),
        );

        $this->getManager()->clear();

        // When
        $client->request('GET', '/agreement/' . $agreementId . '/activity-log');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(4, $payload['total'], 'only logs of this agreement returned');
        $this->assertCount(4, $payload['items']);

        // Newest first
        $this->assertEquals(AgreementActivityLogType::AGREEMENT_LINE_ARCHIVED->value, $payload['items'][0]['type']);
        $this->assertEquals(AgreementActivityLogType::AGREEMENT_LINE_CREATED->value, $payload['items'][1]['type']);
        $this->assertEquals(AgreementActivityLogType::AGREEMENT_LINE_CREATED->value, $payload['items'][2]['type']);
        $this->assertEquals(AgreementActivityLogType::AGREEMENT_CREATED->value, $payload['items'][3]['type']);

        // Content is translated (with productName interpolated for line-level logs)
        $this->assertEquals("Zmiana statusu na 'Archiwum' (Krzesło Beton)", $payload['items'][0]['content']);
        $this->assertEquals('Utworzenie zamówienia', $payload['items'][3]['content']);

        // User info present (id + name)
        foreach ($payload['items'] as $item) {
            $this->assertNotNull($item['user']);
            $this->assertEquals($user->getId(), $item['user']['id']);
            $this->assertNotEmpty($item['user']['name']);
        }

        // Every returned log has agreementId field with our agreement id
        foreach ($payload['items'] as $item) {
            $fieldsByName = [];
            foreach ($item['fields'] as $f) {
                $fieldsByName[$f['name']] = $f['value'];
            }
            $this->assertSame((string) $agreementId, $fieldsByName['agreementId']);
        }
    }

    public function testShouldIncludeRelatedProductionLogs(): void
    {
        // Given — production logs carry the agreementId of their line, so they must surface here too
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $agreementId = 555;
        $lineId = 777;

        $this->seedLog(
            AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_STATUS_CHANGED->value,
            'activity_log.agreement_line.production_status_changed',
            $user,
            ['id' => (string) $lineId, 'agreementId' => (string) $agreementId],
            new \DateTime('2026-05-01 10:00:00'),
            ['departmentName' => 'Klejenie', 'statusName' => 'W trakcie'],
        );
        $this->seedLog(
            AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED->value,
            'activity_log.agreement_line.production_date_start_changed',
            $user,
            ['id' => (string) $lineId, 'agreementId' => (string) $agreementId],
            new \DateTime('2026-05-01 11:00:00'),
            ['departmentName' => 'CNC', 'oldDate' => '2026-04-01', 'newDate' => '2026-04-10'],
        );
        $this->getManager()->clear();

        // When
        $client->request('GET', '/agreement/' . $agreementId . '/activity-log');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);

        $types = array_column($payload['items'], 'type');
        $this->assertContains(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_STATUS_CHANGED->value, $types);
        $this->assertContains(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED->value, $types);
        $this->assertEquals(2, $payload['total']);
    }

    public function testShouldReturnEmptyListForAgreementWithoutLogs(): void
    {
        // Given — no logs at all for agreementId=42
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);
        $this->getManager()->clear();

        // When
        $client->request('GET', '/agreement/42/activity-log');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $payload['total']);
        $this->assertSame([], $payload['items']);
    }

    public function testShouldReturn403WithoutGrant(): void
    {
        $user = $this->createUser();
        $client = $this->login($user);
        $this->getManager()->clear();

        $client->request('GET', '/agreement/1/activity-log');

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldRespectPaginationParameters(): void
    {
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $agreementId = 7;
        for ($i = 1; $i <= 5; $i++) {
            $this->seedLog(
                AgreementActivityLogType::AGREEMENT_LINE_CREATED->value,
                'activity_log.agreement_line.created',
                $user,
                ['id' => (string) $i, 'agreementId' => (string) $agreementId],
                new \DateTime(sprintf('2026-05-%02d 09:00:00', $i)),
            );
        }
        $this->getManager()->clear();

        $client->request('GET', '/agreement/' . $agreementId . '/activity-log?page=2&pageSize=2');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(5, $payload['total']);
        $this->assertEquals(2, $payload['page']);
        $this->assertEquals(2, $payload['pageSize']);
        $this->assertCount(2, $payload['items']);
    }

    /**
     * @param array<string, string> $fields
     * @param array<string, mixed>|null $contentParams
     */
    private function seedLog(
        string $type,
        string $content,
        User $user,
        array $fields,
        ?\DateTimeInterface $createdAt = null,
        ?array $contentParams = null,
    ): ActivityLog {
        $log = new ActivityLog($type, $content, $user, LogLevel::INFO, LogPriority::normal, $contentParams);
        foreach ($fields as $name => $value) {
            $log->addLogField((string) $name, (string) $value);
        }
        $this->activityLogRepository->save($log, true);

        if ($createdAt !== null) {
            $log->setCreatedAt($createdAt);
            $this->getManager()->flush();
        }

        return $log;
    }
}
