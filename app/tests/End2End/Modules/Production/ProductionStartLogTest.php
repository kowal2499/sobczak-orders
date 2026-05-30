<?php

namespace App\Tests\End2End\Modules\Production;

use App\Entity\AgreementLine;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class ProductionStartLogTest extends ApiTestCase
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

    public function testShouldLogProductionStartedWhenStartingProduction(): void
    {
        // Given
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $line = $this->chainFactory->make([], ['status' => AgreementLine::STATUS_WAITING]);
        $lineId = $line->getId();
        $agreementId = $line->getAgreement()->getId();
        $this->getManager()->clear();

        // When
        $client->request('POST', '/production/start/' . $lineId);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $logs = $this->activityLogRepository->findBy(
            ['type' => AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_STARTED->value],
        );
        $this->assertCount(1, $logs, 'Exactly one production_started log expected');

        /** @var ActivityLog $log */
        $log = $logs[0];
        $this->assertEquals('activity_log.agreement_line.production_started', $log->getContent());
        $this->assertEquals($user->getId(), $log->getUser()?->getId());

        $fields = [];
        foreach ($log->getLogFields() as $field) {
            $fields[$field->getName()] = $field->getValue();
        }
        $this->assertEquals(
            ['id' => (string) $lineId, 'agreementId' => (string) $agreementId],
            $fields,
        );

        // Sanity — endpoint really moved the line to MANUFACTURING
        $reloaded = $this->getManager()->find(AgreementLine::class, $lineId);
        $this->assertSame(AgreementLine::STATUS_MANUFACTURING, $reloaded->getStatus());
    }
}
