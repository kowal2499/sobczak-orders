<?php

namespace App\Module\MetricSnapshot\CommandHandler;

use App\Module\ActivityLog\Command\AddActivityLogCommand;
use App\Module\MetricSnapshot\Command\CaptureMetricSnapshotCommand;
use App\Module\MetricSnapshot\DTO\MetricValueDTO;
use App\Module\MetricSnapshot\Entity\MetricSnapshot;
use App\Module\MetricSnapshot\Repository\MetricSnapshotRepository;
use App\Module\MetricSnapshot\Service\MetricSnapshotCollector;
use App\Module\MetricSnapshot\ValueObject\SnapshotSource;
use App\System\CommandBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Lock\LockFactory;

/**
 * Przelicza i zastępuje w całości wiersze dnia. Miniony dzień jest zamrożony - wolno go tylko
 * uzupełnić (backfill), i to wyłącznie gdy nie ma jeszcze żadnego zapisu.
 */
class CaptureMetricSnapshotCommandHandler
{
    private const LOCK = 'metric_snapshot_capture';

    public function __construct(
        private readonly MetricSnapshotCollector $collector,
        private readonly MetricSnapshotRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly LockFactory $lockFactory,
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(CaptureMetricSnapshotCommand $command): void
    {
        $day = new \DateTimeImmutable($command->date);
        $this->guard($day, $command->source);

        // cron i przycisk mogą trafić na siebie - drugi nie czeka, tylko odpada
        $lock = $this->lockFactory->createLock(self::LOCK);
        if (!$lock->acquire()) {
            throw new \InvalidArgumentException('Przeliczenie już trwa - spróbuj za chwilę.');
        }

        try {
            $values = $this->collector->collect($day, SnapshotSource::BACKFILL !== $command->source);
            $this->replaceDay($day, $values, $command->source);
        } finally {
            $lock->release();
        }

        if (SnapshotSource::MANUAL === $command->source) {
            $this->commandBus->dispatch(new AddActivityLogCommand(
                message: 'activity_log.metric_snapshot.captured',
                type: 'metric_snapshot.captured',
                contextData: ['snapshotDate' => $day->format('Y-m-d')],
                authorUserId: $command->userId,
                contentParams: [
                    'date' => $day->format('Y-m-d'),
                    'rows' => count($values),
                ],
            ));
        }
    }

    private function guard(\DateTimeImmutable $day, SnapshotSource $source): void
    {
        $today = new \DateTimeImmutable('today');

        if ($day > $today) {
            throw new \InvalidArgumentException('Nie można zapisać mierników dla przyszłego dnia.');
        }
        if ($day == $today) {
            if (SnapshotSource::BACKFILL === $source) {
                throw new \InvalidArgumentException('Uzupełnianie wstecz dotyczy wyłącznie minionych dni.');
            }

            return;
        }
        if (SnapshotSource::BACKFILL !== $source) {
            throw new \InvalidArgumentException('Miniony dzień jest zamrożony - można go tylko uzupełnić wstecz.');
        }
        if ($this->repository->hasAnyForDate($day)) {
            throw new \InvalidArgumentException(sprintf(
                'Dzień %s ma już zapis - zamrożonej historii nie nadpisujemy.',
                $day->format('Y-m-d')
            ));
        }
    }

    /**
     * @param MetricValueDTO[] $values
     */
    private function replaceDay(\DateTimeImmutable $day, array $values, SnapshotSource $source): void
    {
        $computedAt = new \DateTimeImmutable();

        $this->em->beginTransaction();
        try {
            // zastępujemy cały dzień - klient, który od ostatniego przebiegu wypadł do zera, nie ma zostać
            $this->repository->deleteForDate($day);
            foreach ($values as $value) {
                $this->em->persist(new MetricSnapshot(
                    $day,
                    $value->metric,
                    $value->dimension,
                    $value->periodKey,
                    $value->customerId,
                    $value->factorSum,
                    $value->itemCount,
                    $value->params,
                    $computedAt,
                    $source,
                ));
            }
            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}
