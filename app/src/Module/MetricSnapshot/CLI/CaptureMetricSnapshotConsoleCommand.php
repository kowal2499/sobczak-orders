<?php

namespace App\Module\MetricSnapshot\CLI;

use App\Module\MetricSnapshot\Command\CaptureMetricSnapshotCommand;
use App\Module\MetricSnapshot\ValueObject\SnapshotSource;
use App\System\CommandBus;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

#[AsCommand(
    name: 'app:metric-snapshot:capture',
    description: 'Zapisuje bieżące wartości mierników pulpitu dla dnia (domyślnie dziś).',
)]
class CaptureMetricSnapshotConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBus $commandBus,
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

        try {
            $this->commandBus->dispatch(new CaptureMetricSnapshotCommand($date, $source));
        } catch (HandlerFailedException $e) {
            $nested = $e->getNestedExceptions()[0] ?? $e;
            $io->error($nested->getMessage());

            return Command::FAILURE;
        } catch (ValidationFailedException $e) {
            $io->error(sprintf('Nieprawidłowa data: %s', $date));

            return Command::FAILURE;
        }

        $io->success(sprintf('Zapisano mierniki dla dnia %s (%s).', $date, $source->value));

        return Command::SUCCESS;
    }
}
