<?php

namespace App\Tests\End2End\Modules\ActivityLog;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Entity\StatusLog;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Repository\ActivityLogRepository;
use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Module\ActivityLog\ValueObject\LogPriority;
use App\System\Test\ApiTestCase;
use App\Tests\Utilities\Factory\AgreementLineChainFactory;
use App\Tests\Utilities\Factory\EntityFactory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateStatusLogsCommandTest extends ApiTestCase
{
    private const TYPE = 'agreement_line.production_status_changed';

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

    public function testShouldRebuildLogsFromStatusLogHistory(): void
    {
        // Given — a production with three real status changes recorded in StatusLog
        $user = $this->createUser();
        $line = $this->chainFactory->make();
        $agreementId = $line->getAgreement()->getId();

        $production = $this->factory->make(Production::class, [
            'agreementLine' => $line,
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
            'status' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            'isGhost' => false,
        ]);

        $sl1 = $this->factory->make(StatusLog::class, ['production' => $production, 'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS, 'user' => $user]);
        $sl2 = $this->factory->make(StatusLog::class, ['production' => $production, 'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING, 'user' => $user]);
        $sl3 = $this->factory->make(StatusLog::class, ['production' => $production, 'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED, 'user' => $user]);
        $this->getManager()->flush();

        // Force distinct historical createdAt (Gedmo sets "now" on insert, so override after the first flush)
        $sl1->setCreatedAt(new \DateTime('2026-05-01 08:00:00'));
        $sl2->setCreatedAt(new \DateTime('2026-05-02 09:00:00'));
        $sl3->setCreatedAt(new \DateTime('2026-05-03 10:00:00'));
        $this->getManager()->flush();

        // And — a stale log of this type that must be overwritten (deleted) by the rebuild
        $stale = new ActivityLog(self::TYPE, 'activity_log.' . self::TYPE, $user, LogLevel::INFO, LogPriority::normal, [
            'departmentName' => 'Klejenie',
            'oldStatusName' => self::staleMarker(),
            'newStatusName' => self::staleMarker(),
        ]);
        $stale->addLogField('id', '999999');
        $stale->addLogField('agreementId', (string) $agreementId);
        $this->getManager()->persist($stale);
        $this->getManager()->flush();

        $lineId = $line->getId();
        $userId = $user->getId();
        $this->getManager()->clear();

        // When
        $tester = $this->runMigration([]);

        // Then — the command rebuilds the whole table, so scope assertions to our own line.
        $this->assertSame(0, $tester->getStatusCode());

        $allLogs = $this->activityLogRepository->findBy(['type' => self::TYPE], ['createdAt' => 'ASC']);

        // Stale log (a non-existent line) is gone — it has no StatusLog to be rebuilt from
        $staleLeft = array_filter($allLogs, fn (ActivityLog $log) => $this->fieldValue($log, 'id') === '999999');
        $this->assertCount(0, $staleLeft, 'Stale log of this type was overwritten/removed');

        // Exactly one log per real status change of our production, chronological old → new chain
        $logs = array_values(array_filter($allLogs, fn (ActivityLog $log) => $this->fieldValue($log, 'id') === (string) $lineId));
        $this->assertCount(3, $logs);
        $this->assertChange($logs[0], '—', 'Oczekuje', '2026-05-01 08:00:00', $lineId, $agreementId, $userId);
        $this->assertChange($logs[1], 'Oczekuje', 'W trakcie', '2026-05-02 09:00:00', $lineId, $agreementId, $userId);
        $this->assertChange($logs[2], 'W trakcie', 'Zakończone', '2026-05-03 10:00:00', $lineId, $agreementId, $userId);
    }

    public function testDryRunMakesNoChanges(): void
    {
        // Given — one production with two status changes and a stale log
        $user = $this->createUser();
        $line = $this->chainFactory->make();
        $production = $this->factory->make(Production::class, [
            'agreementLine' => $line,
            'departmentSlug' => TaskTypes::TYPE_DEFAULT_SLUG_GLUING,
            'status' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING,
            'isGhost' => false,
        ]);
        $this->factory->make(StatusLog::class, ['production' => $production, 'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_AWAITS, 'user' => $user]);
        $this->factory->make(StatusLog::class, ['production' => $production, 'currentStatus' => TaskTypes::TYPE_DEFAULT_STATUS_PENDING, 'user' => $user]);

        $stale = new ActivityLog(self::TYPE, 'activity_log.' . self::TYPE, $user, LogLevel::INFO, LogPriority::normal, [
            'departmentName' => 'Klejenie',
            'oldStatusName' => self::staleMarker(),
            'newStatusName' => self::staleMarker(),
        ]);
        $stale->addLogField('id', '999999');
        $this->getManager()->persist($stale);
        $this->getManager()->flush();
        $this->getManager()->clear();

        // When
        $tester = $this->runMigration(['--dry-run' => true]);

        // Then — the only log of this type is still the untouched stale one
        $this->assertSame(0, $tester->getStatusCode());
        $logs = $this->activityLogRepository->findBy(['type' => self::TYPE]);
        $this->assertCount(1, $logs);
        $this->assertSame(self::staleMarker(), $logs[0]->getContentParams()['newStatusName'] ?? null);
        $this->assertStringContainsString('DRY RUN', $tester->getDisplay());
    }

    /**
     * @param array<string, mixed> $input
     */
    private function runMigration(array $input): CommandTester
    {
        $this->getManager(); // ensure the kernel is booted
        $application = new Application(self::$kernel);
        $command = $application->find('app:activity-log:migrate-status-logs');

        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }

    private function assertChange(
        ActivityLog $log,
        string $expectedOld,
        string $expectedNew,
        string $expectedCreatedAt,
        int $lineId,
        int $agreementId,
        int $userId,
    ): void {
        $params = $log->getContentParams();
        $this->assertSame('Klejenie', $params['departmentName']);
        $this->assertSame($expectedOld, $params['oldStatusName']);
        $this->assertSame($expectedNew, $params['newStatusName']);
        $this->assertSame($expectedCreatedAt, $log->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertSame($userId, $log->getUser()?->getId());

        $fields = [];
        foreach ($log->getLogFields() as $field) {
            $fields[$field->getName()] = $field->getValue();
        }
        $this->assertSame((string) $lineId, $fields['id']);
        $this->assertSame((string) $agreementId, $fields['agreementId']);
    }

    private function fieldValue(ActivityLog $log, string $name): ?string
    {
        foreach ($log->getLogFields() as $field) {
            if ($field->getName() === $name) {
                return $field->getValue();
            }
        }

        return null;
    }

    private static function staleMarker(): string
    {
        return 'STALE-TO-BE-DELETED';
    }
}
