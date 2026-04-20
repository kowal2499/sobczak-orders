<?php

namespace App\Module\Task\CLI;

use App\Entity\Production;
use App\Module\Agreement\Command\UpdateAgreementLineRM;
use App\Module\Task\Entity\Task;
use App\Module\Task\Entity\TaskStatusLog;
use App\Module\Task\ValueObject\TaskStatusEnum;
use App\Module\Task\ValueObject\TaskTypeEnum;
use App\Repository\ProductionRepository;
use App\System\CommandBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCustomTasksCommand extends Command
{
    protected static $defaultName = 'app:task:migrate-custom-tasks';
    protected static $defaultDescription = 'Migrate custom_task records from Production to Task module';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductionRepository $productionRepository,
        private readonly CommandBus $commandBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Migrating custom_task records from Production to Task module');

        // Znajdź wszystkie Production z departmentSlug='custom_task'
        $customTaskProductions = $this->productionRepository->findBy(['departmentSlug' => 'custom_task']);

        $totalCount = count($customTaskProductions);

        if ($totalCount === 0) {
            $io->success('No custom_task records found. Nothing to migrate.');
            return Command::SUCCESS;
        }

        $io->note(sprintf('Found %d custom_task records to migrate', $totalCount));

        $migratedCount = 0;
        $batchSize = 100;
        $affectedAgreementLineIds = [];

        foreach ($customTaskProductions as $index => $production) {
            try {
                $this->migrateProductionToTask($production);
                $migratedCount++;

                // Zbierz ID AgreementLine do późniejszej aktualizacji read model
                $agreementLineId = $production->getAgreementLine()->getId();
                $affectedAgreementLineIds[$agreementLineId] = true;

                // Flush co 100 rekordów
                if (($index + 1) % $batchSize === 0) {
                    $this->em->flush();
                    $io->writeln(sprintf('Flushed batch: %d/%d records migrated', $index + 1, $totalCount));
                }
            } catch (\Exception $e) {
                $io->error(sprintf(
                    'Failed to migrate Production ID %d: %s',
                    $production->getId(),
                    $e->getMessage()
                ));
            }
        }

        // Flush pozostałych rekordów
        $this->em->flush();

        $io->success(sprintf('Successfully migrated %d out of %d custom_task records', $migratedCount, $totalCount));

        // Aktualizacja read models dla wszystkich dotkniętych AgreementLine
        $affectedCount = count($affectedAgreementLineIds);
        if ($affectedCount > 0) {
            $io->section('Updating read models for affected AgreementLines');
            $io->note(sprintf('Updating %d AgreementLine read models', $affectedCount));

            $updatedCount = 0;
            foreach (array_keys($affectedAgreementLineIds) as $agreementLineId) {
                try {
                    $this->commandBus->dispatch(new UpdateAgreementLineRM($agreementLineId));
                    $updatedCount++;

                    if ($updatedCount % 50 === 0) {
                        $io->writeln(sprintf('Updated %d/%d read models', $updatedCount, $affectedCount));
                    }
                } catch (\Exception $e) {
                    $io->warning(sprintf(
                        'Failed to update read model for AgreementLine ID %d: %s',
                        $agreementLineId,
                        $e->getMessage()
                    ));
                }
            }

            $io->success(sprintf('Successfully updated %d read models', $updatedCount));
        }

        return Command::SUCCESS;
    }

    private function migrateProductionToTask(Production $production): void
    {
        $task = new Task();

        $task->setAgreementLine($production->getAgreementLine());
        $task->setDateStart($production->getDateStart());
        $task->setDateEnd($production->getDateEnd());

        $status = $this->mapStatus($production->getStatus());
        $task->setStatusEnum($status);

        $task->setTypeEnum(TaskTypeEnum::TASK_CUSTOM);
        $task->setTitle($production->getTitle());
        $task->setDescription($production->getDescription());
        $task->setOwner(null); // Production nie ma pola owner
        $task->setIsDeleted(false);

        $this->em->persist($task);

        foreach ($production->getStatusLogs() as $statusLog) {
            $log = new TaskStatusLog(
                $task,
                $this->mapStatus($statusLog->getCurrentStatus())->value,
                null,
                $statusLog->getUser()
            );
            $log->setCreatedAt($statusLog->getCreatedAt());
            $this->em->persist($log);
        }
    }

    private function mapStatus(?string $productionStatus): TaskStatusEnum
    {
        // Production używa stringów, ale w definicjach są stałe liczbowe
        // TaskTypes::TYPE_CUSTOM_STATUS_AWAITS = 10
        // TaskTypes::TYPE_CUSTOM_STATUS_PENDING = 11
        // TaskTypes::TYPE_CUSTOM_STATUS_COMPLETED = 12

        // Jeśli status w Production jest zapisany jako liczba w stringu
        if (is_numeric($productionStatus)) {
            $statusInt = (int) $productionStatus;
            return match ($statusInt) {
                10 => TaskStatusEnum::AWAITS,
                11 => TaskStatusEnum::PENDING,
                12 => TaskStatusEnum::COMPLETED,
                default => TaskStatusEnum::AWAITS,
            };
        }

        // Domyślnie AWAITS
        return TaskStatusEnum::AWAITS;
    }
}
