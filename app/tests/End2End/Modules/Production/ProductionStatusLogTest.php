<?php

namespace App\Tests\End2End\Modules\Production;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class ProductionStatusLogTest extends ApiTestCase
{
    private ActivityLogRepository $activityLogRepository;
    private EntityFactory $factory;
    private AgreementLineChainFactory $chainFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->activityLogRepository = $this->get(ActivityLogRepository::class);
        $this->factory = new EntityFactory($this->getManager());
        $this->chainFactory = new AgreementLineChainFactory($this->factory);
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldLogStatusChangeViaUpdateStatusEndpoint(): void
    {
        // Given — a non-ghost production (Klejenie, AWAITS) on a line
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $line = $this->chainFactory->make();
        $production = $this->factory->make(Production::class, [
            'agreementLine' => $line,
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
            'status' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
            'isGhost' => false,
        ]);
        $this->getManager()->flush();

        $productionId = $production->getId();
        $lineId = $line->getId();
        $agreementId = $line->getAgreement()->getId();
        $this->getManager()->clear();

        // When — move to IN_PROGRESS (2)
        $client->request('POST', '/production/update_status', [
            'productionId' => $productionId,
            'newStatus' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
        ]);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $logs = $this->activityLogRepository->findBy([
            'type' => AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_STATUS_CHANGED->value,
        ]);
        $this->assertCount(1, $logs);

        /** @var ActivityLog $log */
        $log = $logs[0];
        // Raw content is the translation key; interpolation happens at read time (query handler).
        $this->assertEquals('activity_log.agreement_line.production_status_changed', $log->getContent());
        $this->assertSame('Oczekuje', $log->getContentParams()['oldStatusName']);
        $this->assertSame('W trakcie', $log->getContentParams()['newStatusName']);
        $this->assertSame('Klejenie', $log->getContentParams()['departmentName']);
        $this->assertEquals($user->getId(), $log->getUser()?->getId());

        $fields = [];
        foreach ($log->getLogFields() as $field) {
            $fields[$field->getName()] = $field->getValue();
        }
        $this->assertSame((string) $lineId, $fields['id']);
        $this->assertSame((string) $agreementId, $fields['agreementId']);
    }

    public function testShouldNotLogWhenStatusUnchanged(): void
    {
        // Given — production already AWAITS, update_status to the same value
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $line = $this->chainFactory->make();
        $production = $this->factory->make(Production::class, [
            'agreementLine' => $line,
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
            'status' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
            'isGhost' => false,
        ]);
        $this->getManager()->flush();
        $productionId = $production->getId();
        $this->getManager()->clear();

        // When — same status
        $client->request('POST', '/production/update_status', [
            'productionId' => $productionId,
            'newStatus' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
        ]);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->getManager()->clear();
        $logs = $this->activityLogRepository->findBy([
            'type' => AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_STATUS_CHANGED->value,
        ]);
        $this->assertCount(0, $logs, 'No log expected when status does not actually change');
    }
}
