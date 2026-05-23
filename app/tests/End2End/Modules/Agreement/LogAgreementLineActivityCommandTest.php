<?php

namespace App\Tests\End2End\Modules\Agreement;

use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\Module\Agreement\Command\LogAgreementLineActivityCommand;
use App\System\CommandBus;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;

class LogAgreementLineActivityCommandTest extends ApiTestCase
{
    private ActivityLogRepository $activityLogRepository;
    private CommandBus $commandBus;
    private AgreementLineChainFactory $chainFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getManager()->beginTransaction();
        $this->activityLogRepository = $this->get(ActivityLogRepository::class);
        $this->commandBus = $this->get(CommandBus::class);
        $this->chainFactory = new AgreementLineChainFactory(new EntityFactory($this->getManager()));
    }

    protected function tearDown(): void
    {
        $this->getManager()->rollback();
        parent::tearDown();
    }

    public function testShouldDispatchAddActivityLogWithFieldsAndType(): void
    {
        // Given
        $line = $this->chainFactory->make();
        $lineId = $line->getId();
        $agreementId = $line->getAgreement()->getId();
        $this->getManager()->clear();

        // When
        $this->commandBus->dispatch(new LogAgreementLineActivityCommand(
            $lineId,
            AgreementActivityLogType::AGREEMENT_LINE_ARCHIVED,
        ));

        // Then
        $this->getManager()->clear();
        $logs = $this->activityLogRepository->findBy(
            ['type' => AgreementActivityLogType::AGREEMENT_LINE_ARCHIVED->value],
        );
        $this->assertCount(1, $logs);

        /** @var ActivityLog $log */
        $log = $logs[0];
        $this->assertEquals(AgreementActivityLogType::AGREEMENT_LINE_ARCHIVED->value, $log->getType());
        $this->assertEquals('activity_log.agreement_line.archived', $log->getContent());

        $fields = [];
        foreach ($log->getLogFields() as $field) {
            $fields[$field->getName()] = $field->getValue();
        }
        $this->assertEquals(
            ['id' => (string) $lineId, 'agreementId' => (string) $agreementId],
            $fields,
        );
    }

    public function testShouldDoNothingWhenAgreementLineMissing(): void
    {
        // Given — no agreement line exists with the given id
        $missingId = 999999999;
        $this->getManager()->clear();

        $logsBefore = count($this->activityLogRepository->findAll());

        // When
        $this->commandBus->dispatch(new LogAgreementLineActivityCommand(
            $missingId,
            AgreementActivityLogType::AGREEMENT_LINE_DELETED,
        ));

        // Then — handler is a no-op, no exception, no log created
        $this->getManager()->clear();
        $logsAfter = count($this->activityLogRepository->findAll());
        $this->assertSame($logsBefore, $logsAfter);
    }
}
