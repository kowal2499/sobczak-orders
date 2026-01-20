<?php

namespace App\Module\AgreementLine\CLI;

use App\Module\AgreementLine\Command\UpdateAgreementLineRM;
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
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Rebuilds read model for all AgreementLines.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $agreementLines = $this->agreementLineRepository->findAll();
        $output->writeln(['', 'AgreementLine Read Model Sync', '========================']);

        $progressBar = new ProgressBar($output, count($agreementLines));
        $progressBar->start();

        foreach ($agreementLines as $agreementLine) {
            $command = new UpdateAgreementLineRM($agreementLine->getId());
            $this->commandBus->dispatch($command);
            $progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln('');

        return Command::SUCCESS;
    }
}