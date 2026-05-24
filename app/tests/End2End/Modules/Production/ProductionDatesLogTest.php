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
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class ProductionDatesLogTest extends ApiTestCase
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

    public function testShouldLogOnlyStartDateChange(): void
    {
        [$client, $lineId, $agreementId] = $this->givenLineWithProduction();

        // start changes, end stays
        $client->jsonRequest('PUT', '/agreement_line/update/' . $lineId, $this->updatePayload('2026-05-10 08:00:00', '2026-04-05 16:00:00'));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();

        $this->assertNoLogsOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_END_CHANGED);
        $log = $this->singleLogOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED);
        $this->assertEquals('activity_log.agreement_line.production_date_start_changed', $log->getContent());
        $params = $log->getContentParams();
        $this->assertSame('Klejenie', $params['departmentName']);
        $this->assertSame('2026-04-01', $params['oldDate']);
        $this->assertSame('2026-05-10', $params['newDate']);
        $this->assertLogFieldsLink($log, $lineId, $agreementId);
    }

    public function testShouldLogOnlyEndDateChange(): void
    {
        [$client, $lineId, $agreementId] = $this->givenLineWithProduction();

        // end changes, start stays
        $client->jsonRequest('PUT', '/agreement_line/update/' . $lineId, $this->updatePayload('2026-04-01 08:00:00', '2026-05-15 16:00:00'));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();

        $this->assertNoLogsOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED);
        $log = $this->singleLogOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_END_CHANGED);
        $params = $log->getContentParams();
        $this->assertSame('2026-04-05', $params['oldDate']);
        $this->assertSame('2026-05-15', $params['newDate']);
        $this->assertLogFieldsLink($log, $lineId, $agreementId);
    }

    public function testShouldLogTwoEntriesWhenBothDatesChange(): void
    {
        [$client, $lineId] = $this->givenLineWithProduction();

        $client->jsonRequest('PUT', '/agreement_line/update/' . $lineId, $this->updatePayload('2026-05-10 08:00:00', '2026-05-15 16:00:00'));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();

        $this->singleLogOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED);
        $this->singleLogOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_END_CHANGED);
    }

    public function testShouldLogGhostDateChangeViaGhostEndpoint(): void
    {
        // Given — a ghost (forecast) production
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $line = $this->chainFactory->make([], ['status' => 0]);
        $production = $this->factory->make(Production::class, [
            'agreementLine' => $line,
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
            'status' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
            'isGhost' => true,
            'dateStart' => new \DateTime('2026-04-01 08:00:00'),
            'dateEnd' => new \DateTime('2026-04-05 16:00:00'),
        ]);
        $this->getManager()->flush();
        $productionId = $production->getId();
        $lineId = $line->getId();
        $agreementId = $line->getAgreement()->getId();
        $this->getManager()->clear();

        // When — move only the forecast start date
        $client->jsonRequest('PUT', '/production/ghost/' . $productionId . '/dates', [
            'dateStart' => '2026-05-10 08:00:00',
            'dateEnd' => '2026-04-05 16:00:00',
        ]);

        // Then
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();

        $this->assertNoLogsOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_END_CHANGED);
        $log = $this->singleLogOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED);
        $params = $log->getContentParams();
        $this->assertSame('Klejenie', $params['departmentName']);
        $this->assertSame('2026-04-01', $params['oldDate']);
        $this->assertSame('2026-05-10', $params['newDate']);
        $this->assertLogFieldsLink($log, $lineId, $agreementId);
    }

    public function testShouldNotLogWhenDatesUnchanged(): void
    {
        [$client, $lineId] = $this->givenLineWithProduction();

        $client->jsonRequest('PUT', '/agreement_line/update/' . $lineId, $this->updatePayload('2026-04-01 08:00:00', '2026-04-05 16:00:00'));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->getManager()->clear();

        $this->assertNoLogsOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED);
        $this->assertNoLogsOfType(AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_END_CHANGED);
    }

    /**
     * @return array{0: KernelBrowser, 1: int, 2: int}
     */
    private function givenLineWithProduction(): array
    {
        $user = $this->createUser([], [], [], ['ROLE_PRODUCTION']);
        $client = $this->login($user);

        $line = $this->chainFactory->make([], ['status' => 0]);
        $this->factory->make(Production::class, [
            'agreementLine' => $line,
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
            'status' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
            'isGhost' => false,
            'dateStart' => new \DateTime('2026-04-01 08:00:00'),
            'dateEnd' => new \DateTime('2026-04-05 16:00:00'),
        ]);
        $this->getManager()->flush();

        $lineId = $line->getId();
        $agreementId = $line->getAgreement()->getId();
        $this->getManager()->clear();

        return [$client, $lineId, $agreementId];
    }

    private function updatePayload(string $dateStart, string $dateEnd): array
    {
        return [
            'status' => 0,
            'confirmedDate' => '2026-04-16 00:00:00',
            'description' => 'desc',
            'factor' => 1,
            'productions' => [
                [
                    'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
                    'status' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS,
                    'dateStart' => $dateStart,
                    'dateEnd' => $dateEnd,
                    'description' => '',
                    'title' => '',
                ],
            ],
            'tags' => [],
        ];
    }

    private function singleLogOfType(AgreementActivityLogType $type): ActivityLog
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

    private function assertLogFieldsLink(ActivityLog $log, int $lineId, int $agreementId): void
    {
        $fields = [];
        foreach ($log->getLogFields() as $field) {
            $fields[$field->getName()] = $field->getValue();
        }
        $this->assertSame((string) $lineId, $fields['id']);
        $this->assertSame((string) $agreementId, $fields['agreementId']);
    }
}
