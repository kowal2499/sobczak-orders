<?php

namespace App\Module\Production\CLI;

use App\Entity\AgreementLine;
use App\Entity\Production;
use App\Module\Agreement\Command\UpdateAgreementLineRM;
use App\Module\Production\Service\GhostProductionTaskService;
use App\Module\Production\ValueObject\DepartmentEnum;
use App\Repository\AgreementLineRepository;
use App\System\CommandBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateGhostTasksForPendingOrders extends Command
{
    protected static $defaultName = 'app:production:ghost-backfill';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly GhostProductionTaskService $ghostService,
        private readonly CommandBus $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Removes all existing ghost production tasks, generates fresh ones for pending agreement lines, and refreshes their read models')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not persist changes, only report')
            ->addOption('batch-size', null, InputOption::VALUE_OPTIONAL, 'Batch size for em flush', 50);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = (bool) $input->getOption('dry-run');
        $batchSize = max(1, (int) $input->getOption('batch-size'));

        $productionDeptSlugs = array_map(
            fn(DepartmentEnum $d) => $d->value,
            DepartmentEnum::getProductionDepartments()
        );

        $io->title(sprintf('Ghost production task backfill (%s)', $isDryRun ? 'DRY RUN' : 'APPLY'));

        $deletedCount = $this->deleteExistingGhosts($productionDeptSlugs, $isDryRun);
        $io->text(sprintf('%s %d existing ghost production rows.', $isDryRun ? 'Would delete' : 'Deleted', $deletedCount));
        $this->em->clear();

        $candidateIds = $this->findCandidateAgreementLineIds($productionDeptSlugs);
        $total = count($candidateIds);
        $io->text(sprintf('Found %d AL needing ghost creation.', $total));

        if ($total > 0) {
            $progress = $io->createProgressBar($total);
            $progress->start();
            $processed = 0;
            foreach ($candidateIds as $alId) {
                $line = $this->agreementLineRepository->find($alId);
                if (!$line) {
                    $progress->advance();
                    continue;
                }
                $this->ghostService->createForAgreementLine($line);
                $processed++;
                $progress->advance();
                if (!$isDryRun && $processed % $batchSize === 0) {
                    $this->em->flush();
                    $this->em->clear();
                }
            }
            if (!$isDryRun) {
                $this->em->flush();
                $this->em->clear();
            }
            $progress->finish();
            $io->newLine(2);
        }

        $rmCandidates = $this->findAgreementLinesWithGhosts($productionDeptSlugs);
        $io->text(sprintf('Refreshing read model for %d AL with ghost productions.', count($rmCandidates)));

        if (!$isDryRun) {
            foreach ($rmCandidates as $alId) {
                $this->commandBus->dispatch(new UpdateAgreementLineRM($alId));
            }
        }

        $io->success(sprintf(
            '%s %d AL with ghosts + RM refreshed.',
            $isDryRun ? 'Would create' : 'Created',
            $total
        ));

        return Command::SUCCESS;
    }

    /**
     * @param string[] $slugs
     */
    private function deleteExistingGhosts(array $slugs, bool $isDryRun): int
    {
        $countQb = $this->em->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Production::class, 'p')
            ->andWhere('p.isGhost = 1')
            ->andWhere('p.departmentSlug IN (:slugs)')
            ->setParameter('slugs', $slugs);
        $count = (int) $countQb->getQuery()->getSingleScalarResult();

        if ($isDryRun || $count === 0) {
            return $count;
        }

        $this->em->createQueryBuilder()
            ->delete(Production::class, 'p')
            ->andWhere('p.isGhost = 1')
            ->andWhere('p.departmentSlug IN (:slugs)')
            ->setParameter('slugs', $slugs)
            ->getQuery()
            ->execute();

        return $count;
    }

    /**
     * @param string[] $slugs
     * @return int[]
     */
    private function findCandidateAgreementLineIds(array $slugs): array
    {
        $qb = $this->agreementLineRepository->createQueryBuilder('al')
            ->select('al.id')
            ->leftJoin(
                'al.productions',
                'p',
                'WITH',
                'p.departmentSlug IN (:slugs)'
            )
            ->andWhere('p.id IS NULL')
            ->andWhere('al.deleted = 0')
            ->andWhere('al.confirmedDate IS NOT NULL')
            ->andWhere('al.status = :statusWaiting')
            ->setParameter('slugs', $slugs)
            ->setParameter('statusWaiting', AgreementLine::STATUS_WAITING);

        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }

    /**
     * @param string[] $slugs
     * @return int[]
     */
    private function findAgreementLinesWithGhosts(array $slugs): array
    {
        $qb = $this->agreementLineRepository->createQueryBuilder('al')
            ->select('al.id')
            ->innerJoin(
                'al.productions',
                'p',
                'WITH',
                'p.departmentSlug IN (:slugs) AND p.isGhost = 1'
            )
            ->andWhere('al.deleted = 0')
            ->andWhere('al.status = :statusWaiting')
            ->setParameter('slugs', $slugs)
            ->setParameter('statusWaiting', AgreementLine::STATUS_WAITING)
            ->groupBy('al.id');

        return array_column($qb->getQuery()->getArrayResult(), 'id');
    }
}
