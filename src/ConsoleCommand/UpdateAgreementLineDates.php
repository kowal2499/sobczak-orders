<?php

namespace App\ConsoleCommand;

use App\Entity\AgreementLine;
use App\Repository\AgreementLineRepository;
use App\Service\AgreementLine\ProductionCompletionDateResolverService;
use App\Service\AgreementLine\ProductionStartDateResolverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateAgreementLineDates extends Command
{
    protected static $defaultName = 'app:agreement-line:update-dates';
    /** @var ProductionCompletionDateResolverService */
    private $completionDateResolverService;
    /** @var ProductionStartDateResolverService */
    private $startDateResolverService;
    /** @var AgreementLineRepository */
    private $agreementLineRepository;
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        ProductionStartDateResolverService $startDateResolverService,
        ProductionCompletionDateResolverService $completionDateResolverService,
        EntityManagerInterface $entityManager,
        string $name = null
    ) {
        parent::__construct($name);
        $this->completionDateResolverService = $completionDateResolverService;
        $this->startDateResolverService = $startDateResolverService;
        $this->entityManager = $entityManager;
        $this->agreementLineRepository = $entityManager->getRepository(AgreementLine::class);
    }

    protected function configure()
    {
        $this
            ->setDescription('Updates `agreement_line.production_start_date` and `agreement_line.production_completion_date` for all rows.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lines = $this->fetchAgreementLines();
        $output->writeln(['', 'AgreementLine productionStartDate and productionCompletionDate Updater', '========================']);
        $output->writeln(sprintf('agreementLines to verify: %d', count($lines)));

        $recordsStartDateUpdated = 0;
        $recordsCompletionDateUpdated = 0;
        foreach ($lines as $line) {
            $startDate = $this->startDateResolverService->getStartDate($line->getProductions());
            if ($startDate != $line->getProductionStartDate()) {
                $this->saveNewStartDate($line, $startDate);
                $recordsStartDateUpdated++;
            }

            $completionDate = $this->completionDateResolverService->getCompletionDate($line->getProductions());
            if ($completionDate != $line->getProductionCompletionDate()) {
                $this->saveNewCompletionDate($line, $completionDate);
                $recordsCompletionDateUpdated++;
            }
        }
        if ($recordsStartDateUpdated || $recordsCompletionDateUpdated) {
            $this->entityManager->flush();
        }
        $output->writeln([
            'Finished!',
            sprintf('agreementLines startDate updated: %d', $recordsStartDateUpdated),
            sprintf('agreementLines completionDate updated: %d', $recordsCompletionDateUpdated),
            '']
        );
        return Command::SUCCESS;
    }

    /**
     * @return AgreementLine[]
     */
    private function fetchAgreementLines(): array
    {
        return $this->agreementLineRepository->findAll();
    }

    private function saveNewCompletionDate(AgreementLine $agreementLine, ?\DateTimeInterface $date)
    {
        $agreementLine->setProductionCompletionDate($date);
    }

    private function saveNewStartDate(AgreementLine $agreementLine, ?\DateTimeInterface $date)
    {
        $agreementLine->setProductionStartDate($date);
    }
}