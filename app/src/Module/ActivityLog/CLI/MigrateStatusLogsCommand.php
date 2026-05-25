<?php

namespace App\Module\ActivityLog\CLI;

use App\Entity\StatusLog;
use App\Entity\User;
use App\Module\ActivityLog\Entity\ActivityLog;
use App\Module\ActivityLog\Entity\LogField;
use App\Module\ActivityLog\ValueObject\LogLevel;
use App\Module\ActivityLog\ValueObject\LogPriority;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\Module\Production\ValueObject\DepartmentEnum;
use App\Module\Production\ValueObject\ProductionTaskStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rebuilds production status-change activity logs from the legacy StatusLog history.
 *
 * StatusLog holds the full per-production status history, so for every change we can derive both the
 * previous (old) and the current (new) status — fixing logs that lacked the old value. The command is
 * idempotent: it deletes all existing `agreement_line.production_status_changed` logs and regenerates
 * them from StatusLog (the single source of truth for those logs).
 *
 * ActivityLog entities are built directly (not via AddActivityLogCommand) on purpose: a historical
 * backfill must not emit ActivityLogWasAddedEvent, and direct construction allows batching and setting
 * a historical createdAt + arbitrary author.
 */
#[AsCommand(
    name: 'app:activity-log:migrate-status-logs',
    description: 'Rebuilds production status-change activity logs from legacy StatusLog history.',
)]
class MigrateStatusLogsCommand extends Command
{
    private const NO_STATUS = '—';

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only report what would change, without touching the database')
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, 'How many logs to persist per flush', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = (bool) $input->getOption('dry-run');
        $batchSize = max(1, (int) $input->getOption('batch-size'));
        $type = AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_STATUS_CHANGED->value;

        $io->title(sprintf('Migracja StatusLog → ActivityLog (%s)', $isDryRun ? 'DRY RUN' : 'APPLY'));

        $existingCount = $this->countExisting($type);
        $rows = $this->fetchStatusLogRows();
        $changes = $this->buildChanges($rows);

        $io->definitionList(
            ['Istniejące logi tego typu (do usunięcia)' => $existingCount],
            ['Wpisy StatusLog' => count($rows)],
            ['Realne zmiany do utworzenia (po pominięciu no-opów)' => count($changes)],
        );

        if ($isDryRun) {
            $io->success('DRY RUN — baza danych nie została zmieniona.');

            return Command::SUCCESS;
        }

        $this->deleteExisting($type);

        $progress = $io->createProgressBar(count($changes));
        $progress->start();

        $batch = [];
        foreach ($changes as $change) {
            $log = $this->buildLog($type, $change);
            $this->em->persist($log);
            $batch[] = [$log, $change['createdAt']];

            if (count($batch) >= $batchSize) {
                $this->flushBatch($batch);
                $batch = [];
            }

            $progress->advance();
        }

        if ($batch !== []) {
            $this->flushBatch($batch);
        }

        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf('Usunięto %d, utworzono %d logów „%s".', $existingCount, count($changes), $type));

        return Command::SUCCESS;
    }

    private function countExisting(string $type): int
    {
        return (int) $this->em
            ->createQuery(sprintf('SELECT COUNT(al.id) FROM %s al WHERE al.type = :type', ActivityLog::class))
            ->setParameter('type', $type)
            ->getSingleScalarResult();
    }

    /**
     * @return list<array<string, mixed>> scalar rows ordered by production, then chronologically
     */
    private function fetchStatusLogRows(): array
    {
        return $this->em->createQueryBuilder()
            ->select(
                'sl.id AS statusLogId',
                'sl.currentStatus AS currentStatus',
                'sl.createdAt AS createdAt',
                'IDENTITY(sl.user) AS userId',
                'p.id AS productionId',
                'p.departmentSlug AS departmentSlug',
                'line.id AS lineId',
                'agr.id AS agreementId',
            )
            ->from(StatusLog::class, 'sl')
            ->innerJoin('sl.production', 'p')
            ->innerJoin('p.agreementLine', 'line')
            ->innerJoin('line.Agreement', 'agr')
            ->orderBy('p.id', 'ASC')
            ->addOrderBy('sl.createdAt', 'ASC')
            ->addOrderBy('sl.id', 'ASC')
            ->getQuery()
            ->getScalarResult();
    }

    /**
     * Walks the per-production history, attaching the previous status as "old" and skipping no-op rows
     * (where the status did not actually change), mirroring UpdateStatusCommandHandler's "skip unchanged".
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{oldStatus: int|null, newStatus: int, departmentSlug: string, lineId: int, agreementId: int, userId: int|null, createdAt: \DateTimeInterface}>
     */
    private function buildChanges(array $rows): array
    {
        $changes = [];
        $prevProductionId = null;
        $prevStatus = null;

        foreach ($rows as $row) {
            $productionId = (int) $row['productionId'];
            $currentStatus = (int) $row['currentStatus'];

            if ($productionId !== $prevProductionId) {
                $prevProductionId = $productionId;
                $prevStatus = null;
            }

            if ($prevStatus !== null && $prevStatus === $currentStatus) {
                continue;
            }

            $changes[] = [
                'oldStatus' => $prevStatus,
                'newStatus' => $currentStatus,
                'departmentSlug' => (string) $row['departmentSlug'],
                'lineId' => (int) $row['lineId'],
                'agreementId' => (int) $row['agreementId'],
                'userId' => $row['userId'] !== null ? (int) $row['userId'] : null,
                'createdAt' => new \DateTimeImmutable((string) $row['createdAt']),
            ];

            $prevStatus = $currentStatus;
        }

        return $changes;
    }

    /**
     * @param array{oldStatus: int|null, newStatus: int, departmentSlug: string, lineId: int, agreementId: int, userId: int|null, createdAt: \DateTimeInterface} $change
     */
    private function buildLog(string $type, array $change): ActivityLog
    {
        $department = DepartmentEnum::tryFrom($change['departmentSlug']);
        $oldStatus = $change['oldStatus'] !== null ? ProductionTaskStatus::tryFrom($change['oldStatus']) : null;
        $newStatus = ProductionTaskStatus::tryFrom($change['newStatus']);

        /** @var User|null $user */
        $user = $change['userId'] !== null ? $this->em->getReference(User::class, $change['userId']) : null;

        $log = new ActivityLog(
            $type,
            'activity_log.' . $type,
            $user,
            LogLevel::INFO,
            LogPriority::normal,
            [
                'departmentName' => $department?->getName() ?? $change['departmentSlug'],
                'oldStatusName' => $oldStatus?->getName() ?? self::NO_STATUS,
                'newStatusName' => $newStatus?->getName() ?? (string) $change['newStatus'],
            ],
        );

        $log->addLogField('id', (string) $change['lineId']);
        $log->addLogField('agreementId', (string) $change['agreementId']);

        return $log;
    }

    /**
     * Persists the batch, then overrides createdAt (Gedmo forces "now" on insert, so it must be set
     * after the first flush) and frees memory.
     *
     * @param list<array{0: ActivityLog, 1: \DateTimeInterface}> $batch
     */
    private function flushBatch(array $batch): void
    {
        $this->em->flush();

        foreach ($batch as [$log, $createdAt]) {
            $log->setCreatedAt($createdAt);
        }

        $this->em->flush();
        $this->em->clear();
    }

    private function deleteExisting(string $type): void
    {
        $this->em
            ->createQuery(sprintf(
                'DELETE FROM %s lf WHERE IDENTITY(lf.log) IN (SELECT al.id FROM %s al WHERE al.type = :type)',
                LogField::class,
                ActivityLog::class,
            ))
            ->setParameter('type', $type)
            ->execute();

        $this->em
            ->createQuery(sprintf('DELETE FROM %s al WHERE al.type = :type', ActivityLog::class))
            ->setParameter('type', $type)
            ->execute();
    }
}
