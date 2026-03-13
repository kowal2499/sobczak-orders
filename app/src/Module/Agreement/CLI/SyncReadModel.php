<?php

namespace App\Module\Agreement\CLI;

use App\Module\Agreement\Command\UpdateAgreementLineRM;
use App\Repository\AgreementLineRepository;
use App\System\CommandBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncReadModel extends Command
{
    protected static $defaultName = 'app:agreement-line:rm-sync';
    public function __construct(
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly CommandBus $commandBus,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Rebuilds read model for all AgreementLines.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $chunkSize = 50;
        $offset = 0;

        $output->writeln(['', 'AgreementLine Read Model Sync', '========================']);

        $totalCount = $this->agreementLineRepository->count([]);
        $progressBar = new ProgressBar($output, $totalCount);
        $progressBar->start();

        while (true) {
            $agreementLines = $this->agreementLineRepository->findBy([], null, $chunkSize, $offset);

            if (empty($agreementLines)) {
                break;
            }

            foreach ($agreementLines as $agreementLine) {
                $command = new UpdateAgreementLineRM($agreementLine->getId(), flush: false);
                $this->commandBus->dispatch($command);
                $progressBar->advance();
            }

            // Wymuś zapis do bazy danych po każdym chunku
            $this->entityManager->flush();
            $this->entityManager->clear();

            $offset += $chunkSize;
        }

        $progressBar->finish();
        $output->writeln('');

        return Command::SUCCESS;
    }
}
