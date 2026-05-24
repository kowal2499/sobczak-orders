<?php

namespace App\Tests\End2End\Modules\Agreement;

use App\Entity\User;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Module\ActivityLog\ValueObject\LogPriority;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class AgreementLineActivityLogControllerTest extends ApiTestCase
{
    private ActivityLogRepository $activityLogRepository;
    private AgreementLineChainFactory $chainFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->activityLogRepository = $this->get(ActivityLogRepository::class);
        $this->chainFactory = new AgreementLineChainFactory(new EntityFactory($this->getManager()));
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldReturnAgreementAndLineLogsMergedOrderedNewestFirst(): void
    {
        // Given
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $line = $this->chainFactory->make();
        $lineId = $line->getId();
        $agreementId = $line->getAgreement()->getId();

        $otherLine = $this->chainFactory->make();
        $otherLineId = $otherLine->getId();

        // Agreement-level log for OUR agreement
        $this->seedLog(
            AgreementActivityLogType::AGREEMENT_CREATED->value,
            'activity_log.agreement.created',
            $user,
            ['agreementId' => (string) $agreementId],
            new \DateTime('2026-05-01 09:00:00'),
        );

        // Two line-level logs for OUR line
        $this->seedLog(
            AgreementActivityLogType::AGREEMENT_LINE_CREATED->value,
            'activity_log.agreement_line.created',
            $user,
            ['id' => (string) $lineId, 'agreementId' => (string) $agreementId],
            new \DateTime('2026-05-01 09:00:01'),
        );
        $this->seedLog(
            AgreementActivityLogType::AGREEMENT_LINE_ARCHIVED->value,
            'activity_log.agreement_line.archived',
            $user,
            ['id' => (string) $lineId, 'agreementId' => (string) $agreementId],
            new \DateTime('2026-05-02 12:00:00'),
        );

        // Line-level log for DIFFERENT line in same agreement (should NOT appear)
        $this->seedLog(
            AgreementActivityLogType::AGREEMENT_LINE_CREATED->value,
            'activity_log.agreement_line.created',
            $user,
            ['id' => (string) $otherLineId, 'agreementId' => (string) $agreementId],
            new \DateTime('2026-05-01 09:00:02'),
        );

        // Agreement log for UNRELATED agreement (should NOT appear)
        $this->seedLog(
            AgreementActivityLogType::AGREEMENT_CREATED->value,
            'activity_log.agreement.created',
            $user,
            ['agreementId' => '99999'],
            new \DateTime('2026-05-03 08:00:00'),
        );

        $this->getManager()->clear();

        // When
        $client->request('GET', '/agreement-line/' . $lineId . '/activity-log');

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(3, $payload['total'], 'only agreement-level for THIS agreement + line-level for THIS line');
        $this->assertCount(3, $payload['items']);

        // Newest first
        $this->assertEquals(AgreementActivityLogType::AGREEMENT_LINE_ARCHIVED->value, $payload['items'][0]['type']);
        $this->assertEquals(AgreementActivityLogType::AGREEMENT_LINE_CREATED->value, $payload['items'][1]['type']);
        $this->assertEquals(AgreementActivityLogType::AGREEMENT_CREATED->value, $payload['items'][2]['type']);

        // Verify the line-level logs are for OUR line, not the other one
        foreach ([$payload['items'][0], $payload['items'][1]] as $lineLog) {
            $fields = [];
            foreach ($lineLog['fields'] as $f) {
                $fields[$f['name']] = $f['value'];
            }
            $this->assertSame((string) $lineId, $fields['id']);
        }
    }

    public function testShouldReturn404WhenLineDoesNotExist(): void
    {
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);
        $this->getManager()->clear();

        $client->request('GET', '/agreement-line/9999999/activity-log');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testShouldReturn403WithoutGrant(): void
    {
        $user = $this->createUser();
        $client = $this->login($user);
        $line = $this->chainFactory->make();
        $lineId = $line->getId();
        $this->getManager()->clear();

        $client->request('GET', '/agreement-line/' . $lineId . '/activity-log');

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testShouldReturnEmptyListForLineWithNoLogs(): void
    {
        $user = $this->createUser([], [], ['activity-log.read']);
        $client = $this->login($user);

        $line = $this->chainFactory->make();
        $lineId = $line->getId();
        $this->getManager()->clear();

        $client->request('GET', '/agreement-line/' . $lineId . '/activity-log');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $payload = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(0, $payload['total']);
        $this->assertSame([], $payload['items']);
    }

    /**
     * @param array<string, string> $fields
     */
    private function seedLog(
        string $type,
        string $content,
        User $user,
        array $fields,
        ?\DateTimeInterface $createdAt = null,
    ): ActivityLog {
        $log = new ActivityLog($type, $content, $user, LogLevel::INFO, LogPriority::normal);
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
