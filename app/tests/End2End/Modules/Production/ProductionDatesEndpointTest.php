<?php

namespace App\Tests\End2End\Modules\Production;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Covers PUT /production/{id}/dates — lightweight calendar move/resize endpoint.
 */
class ProductionDatesEndpointTest extends ApiTestCase
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

    public function testShouldRescheduleRealPendingTaskAndLogStartChange(): void
    {
        [$client, $productionId] = $this->givenProduction(TaskTypes::TYPE_DEFAULT_STATUS_AWAITS, false);

        $client->jsonRequest('PUT', '/production/' . $productionId . '/dates', [
            'dateStart' => '2026-05-10 08:00:00',
            'dateEnd' => '2026-04-05 16:00:00',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();

        $this->assertNoLogsOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_END_CHANGED);
        $log = $this->singleLogOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED);
        $params = $log->getContentParams();
        $this->assertSame('2026-04-01', $params['oldDate']);
        $this->assertSame('2026-05-10', $params['newDate']);
    }

    public function testShouldLogTwoEntriesWhenBothDatesChange(): void
    {
        [$client, $productionId] = $this->givenProduction(TaskTypes::TYPE_DEFAULT_STATUS_STARTED, false);

        $client->jsonRequest('PUT', '/production/' . $productionId . '/dates', [
            'dateStart' => '2026-05-10 08:00:00',
            'dateEnd' => '2026-05-15 16:00:00',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();

        $this->singleLogOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED);
        $this->singleLogOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_END_CHANGED);
    }

    public function testShouldRejectCompletedTask(): void
    {
        [$client, $productionId] = $this->givenProduction(TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, false);

        $client->jsonRequest('PUT', '/production/' . $productionId . '/dates', [
            'dateStart' => '2026-05-10 08:00:00',
            'dateEnd' => '2026-05-15 16:00:00',
        ]);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();

        $this->assertNoLogsOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED);
        $this->assertNoLogsOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_END_CHANGED);
    }

    public function testShouldRescheduleGhostTask(): void
    {
        [$client, $productionId] = $this->givenProduction(TaskTypes::TYPE_DEFAULT_STATUS_AWAITS, true);

        $client->jsonRequest('PUT', '/production/' . $productionId . '/dates', [
            'dateStart' => '2026-05-10 08:00:00',
            'dateEnd' => '2026-04-05 16:00:00',
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();

        $log = $this->singleLogOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED);
        $params = $log->getContentParams();
        $this->assertSame('2026-04-01', $params['oldDate']);
        $this->assertSame('2026-05-10', $params['newDate']);
    }

    public function testShouldForbidWithoutGrant(): void
    {
        // User without the production.panel grant
        $unauthorized = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($unauthorized);

        $line = $this->chainFactory->make([], ['status' => 0]);
        $production = $this->factory->make(Production::class, [
            'agreementLine' => $line,
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
            'status' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
            'isGhost' => false,
            'dateStart' => new \DateTime('2026-04-01 08:00:00'),
            'dateEnd' => new \DateTime('2026-04-05 16:00:00'),
        ]);
        $this->getManager()->flush();
        $productionId = $production->getId();
        $this->getManager()->clear();

        $client->jsonRequest('PUT', '/production/' . $productionId . '/dates', [
            'dateStart' => '2026-05-10 08:00:00',
            'dateEnd' => '2026-04-05 16:00:00',
        ]);

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    /**
     * @return array{0: KernelBrowser, 1: int}
     */
    private function givenProduction(int $status, bool $isGhost): array
    {
        $user = $this->createUser([], [], ['production.panel'], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $line = $this->chainFactory->make([], ['status' => 0]);
        $production = $this->factory->make(Production::class, [
            'agreementLine' => $line,
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
            'status' => $status,
            'isGhost' => $isGhost,
            'dateStart' => new \DateTime('2026-04-01 08:00:00'),
            'dateEnd' => new \DateTime('2026-04-05 16:00:00'),
        ]);
        $this->getManager()->flush();

        $productionId = $production->getId();
        $this->getManager()->clear();

        return [$client, $productionId];
    }

    private function singleLogOfType(AgreementActivityLogType $type): \App\Module\ActivityLog\Entity\ActivityLog
    {
        $logs = $this->activityLogRepository->findBy(['type' => $type->value]);
        $this->assertCount(1, $logs, sprintf('Expected exactly one log of type %s', $type->value));
        return $logs[0];
    }

    private function assertNoLogsOfType(AgreementActivityLogType $type): void
    {
        $this->assertCount(
            0,
            $this->activityLogRepository->findBy(['type' => $type->value]),
            sprintf('Expected no logs of type %s', $type->value),
        );
    }
}
