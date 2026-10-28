<?php

namespace App\Module\MetricSnapshot\CLI;

use App\Module\MetricSnapshot\Command\CaptureMetricSnapshotCommand;
use App\Module\MetricSnapshot\Repository\MetricSnapshotRepository;
use App\Module\MetricSnapshot\ValueObject\SnapshotSource;
use App\System\CommandBus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

/**
 * Każdy przebieg trafia też do kanału metric_snapshot (plik dzienny, rotacja 14 dni) - cron
 * nie musi przekierowywać wyjścia do pliku, który rośnie bez końca.
 */
#[AsCommand(
    name: 'app:metric-snapshot:capture',
    description: 'Zapisuje bieżące wartości mierników pulpitu dla dnia (domyślnie dziś).',
)]
class CaptureMetricSnapshotConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly MetricSnapshotRepository $repository,
        private readonly LoggerInterface $metricSnapshotLogger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('date', null, InputOption::VALUE_REQUIRED, 'Dzień w formacie Y-m-d')
            ->addOption(
                'backfill',
                null,
                InputOption::VALUE_NONE,
                'Uzupełnij miniony dzień bez zapisu (tylko mierniki przepływowe, liczone na dzisiejszych danych)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $date = $input->getOption('date') ?? (new \DateTimeImmutable('today'))->format('Y-m-d');
        $source = $input->getOption('backfill') ? SnapshotSource::BACKFILL : SnapshotSource::CRON;
        $startedAt = microtime(true);

        try {
            $this->commandBus->dispatch(new CaptureMetricSnapshotCommand($date, $source));
        } catch (ValidationFailedException) {
            return $this->fail($io, sprintf('Nieprawidłowa data: %s', $date), $date, $source);
        } catch (HandlerFailedException $e) {
            $nested = $e->getNestedExceptions()[0] ?? $e;

            return $this->fail($io, $nested->getMessage(), $date, $source);
        } catch (\Throwable $e) {
            $this->fail($io, $e->getMessage(), $date, $source);
            throw $e;
        }

        $rows = $this->repository->countForDate(new \DateTimeImmutable($date));
        $seconds = round(microtime(true) - $startedAt, 1);

        $this->metricSnapshotLogger->info('Zapisano mierniki', [
            'date' => $date,
            'source' => $source->value,
            'rows' => $rows,
            'seconds' => $seconds,
        ]);
        $io->success(sprintf(
            '%s - zapisano %d wierszy dla dnia %s (%s) w %s s.',
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $rows,
            $date,
            $source->value,
            $seconds
        ));

        return Command::SUCCESS;
    }

    private function fail(SymfonyStyle $io, string $message, string $date, SnapshotSource $source): int
    {
        $this->metricSnapshotLogger->error('Zapis mierników nie powiódł się: ' . $message, [
            'date' => $date,
            'source' => $source->value,
        ]);
        $io->error($message);

        return Command::FAILURE;
    }
}
