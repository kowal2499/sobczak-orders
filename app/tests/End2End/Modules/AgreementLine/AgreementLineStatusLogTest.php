<?php

namespace App\Tests\End2End\Modules\AgreementLine;

use App\Entity\AgreementLine;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class AgreementLineStatusLogTest extends ApiTestCase
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

    public function testShouldLogArchivedWhenSettingStatusToArchived(): void
    {
        $this->assertStatusChangeIsLogged(
            AgreementLine::STATUS_ARCHIVED,
            AgreementActivityLogType::AGREEMENT_LINE_ARCHIVED,
            'activity_log.agreement_line.archived',
        );
    }

    public function testShouldLogSentToWarehouseWhenSettingStatusToWarehouse(): void
    {
        $this->assertStatusChangeIsLogged(
            AgreementLine::STATUS_WAREHOUSE,
            AgreementActivityLogType::AGREEMENT_LINE_SENT_TO_WAREHOUSE,
            'activity_log.agreement_line.sent_to_warehouse',
        );
    }

    public function testShouldLogRestoredWhenSettingStatusToWaiting(): void
    {
        $this->assertStatusChangeIsLogged(
            AgreementLine::STATUS_WAITING,
            AgreementActivityLogType::AGREEMENT_LINE_RESTORED,
            'activity_log.agreement_line.restored',
        );
    }

    public function testShouldLogStatusChangeWhenUpdateChangesStatus(): void
    {
        // Given — existing line in WAITING, then PUT with status=WAREHOUSE
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $line = $this->chainFactory->make([], ['status' => AgreementLine::STATUS_WAITING]);
        $lineId = $line->getId();
        $agreementId = $line->getAgreement()->getId();
        $this->getManager()->clear();

        // When
        $client->request(
            'PUT',
            '/agreement_line/update/' . $lineId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'status' => AgreementLine::STATUS_WAREHOUSE,
                'confirmedDate' => '2026-04-16 00:00:00',
                'description' => 'updated',
                'factor' => 1,
                'productions' => [],
                'tags' => [],
            ]),
        );

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $this->assertLogMatches(
            AgreementActivityLogType::AGREEMENT_LINE_SENT_TO_WAREHOUSE,
            'activity_log.agreement_line.sent_to_warehouse',
            $lineId,
            $agreementId,
            $user->getId(),
        );
    }

    public function testShouldNotLogStatusChangeWhenUpdateKeepsSameStatus(): void
    {
        // Given — existing line in WAREHOUSE; update payload keeps the same status
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $line = $this->chainFactory->make([], ['status' => AgreementLine::STATUS_WAREHOUSE]);
        $lineId = $line->getId();
        $this->getManager()->clear();

        // When
        $client->request(
            'PUT',
            '/agreement_line/update/' . $lineId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'status' => AgreementLine::STATUS_WAREHOUSE,
                'confirmedDate' => '2026-04-16 00:00:00',
                'description' => 'edited description, same status',
                'factor' => 1.5,
                'productions' => [],
                'tags' => [],
            ]),
        );

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $logs = $this->activityLogRepository->findAll();
        $this->assertCount(0, $logs, 'No activity log should be emitted when status does not change');
    }

    public function testShouldLogDeletedWhenDeletingAgreementLine(): void
    {
        // Given
        $user = $this->createUser([], [], [], ['ROLE_ADMIN']);
        $client = $this->login($user);

        $line = $this->chainFactory->make();
        $lineId = $line->getId();
        $agreementId = $line->getAgreement()->getId();
        $this->getManager()->clear();

        // When
        $client->request('POST', '/agreement_line/delete/' . $lineId);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $this->assertLogMatches(
            AgreementActivityLogType::AGREEMENT_LINE_DELETED,
            'activity_log.agreement_line.deleted',
            $lineId,
            $agreementId,
            $user->getId(),
        );

        $reloaded = $this->getManager()->find(AgreementLine::class, $lineId);
        $this->assertTrue($reloaded->getDeleted(), 'Agreement line should be soft-deleted');
    }

    private function assertStatusChangeIsLogged(
        int $statusId,
        AgreementActivityLogType $expectedType,
        string $expectedContent,
    ): void {
        // Given
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $line = $this->chainFactory->make();
        $lineId = $line->getId();
        $agreementId = $line->getAgreement()->getId();
        $this->getManager()->clear();

        // When
        $client->request('POST', '/agreement_line/archive/' . $lineId . '/' . $statusId);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $this->assertLogMatches($expectedType, $expectedContent, $lineId, $agreementId, $user->getId());

        $reloaded = $this->getManager()->find(AgreementLine::class, $lineId);
        $this->assertSame($statusId, $reloaded->getStatus(), 'Status should reflect the requested change');
    }

    private function assertLogMatches(
        AgreementActivityLogType $type,
        string $expectedContent,
        int $lineId,
        int $agreementId,
        int $userId,
    ): void {
        $logs = $this->activityLogRepository->findBy(['type' => $type->value]);
        $this->assertCount(1, $logs, sprintf('Expected exactly one log of type %s', $type->value));

        /** @var ActivityLog $log */
        $log = $logs[0];
        $this->assertEquals($expectedContent, $log->getContent());
        $this->assertEquals($userId, $log->getUser()?->getId());

        $fields = [];
        foreach ($log->getLogFields() as $field) {
            $fields[$field->getName()] = $field->getValue();
        }
        $this->assertEquals(
            ['id' => (string) $lineId, 'agreementId' => (string) $agreementId],
            $fields,
        );
    }
}
