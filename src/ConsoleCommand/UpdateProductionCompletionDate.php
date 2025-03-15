<?php

namespace App\ConsoleCommand;

use App\Entity\Definitions\TaskTypes;
use App\Entity\StatusLog;
use App\Repository\ProductionRepository;
use App\Repository\StatusLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateProductionCompletionDate extends Command
{
    protected static $defaultName = "app:production:update-completion-date";
    private $productionRepository;
    private $statusLogRepository;
    private $entityManager;

    public function __construct(
        ProductionRepository $productionRepository,
        StatusLogRepository $statusLogRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->productionRepository = $productionRepository;
        $this->statusLogRepository = $statusLogRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Updates `production.completed_at` for all rows.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $productions = $this->getProductions();
        $output->writeln(['', 'ProductionCompletionDate Updater', '========================']);
        $output->writeln(sprintf('agreementLines to verify: %d', count($productions)));

        $updatedCounter = 0;
        foreach ($this->getProductions() as $production) {
            $searchStatus = (TaskTypes::getTaskTypeBySlug($production->getDepartmentSlug()) === TaskTypes::TYPE_DEFAULT)
                ? TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED
                : TaskTypes::TYPE_CUSTOM_STATUS_COMPLETED;

            /** @var StatusLog $statusLog */
            $statusLog = $this->statusLogRepository->findLast($production, $searchStatus);

            if (!$statusLog) {
                continue;
            }
            $production->setCompletedAt($statusLog->getCreatedAt());
            $updatedCounter++;
        }
        $this->entityManager->flush();
        $output->writeln([ 'Finished!', sprintf('production is_compled updated: %d', $updatedCounter), '']);
        return Command::SUCCESS;
    }

    private function getProductions()
    {
        return $this->productionRepository->findBy(['isCompleted' => 1, 'completedAt' => null]);
    }
}