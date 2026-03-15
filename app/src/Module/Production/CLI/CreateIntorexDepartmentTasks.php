<?php

namespace App\Module\Production\CLI;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Entity\Department;
use App\Entity\Production;
use App\Repository\AgreementLineRepository;
use App\Service\AgreementLine\ProductionCompletionDateResolverService;
use App\Service\AgreementLine\ProductionStartDateResolverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateIntorexDepartmentTasks extends Command
{
    protected static $defaultName = 'app:production:intorex-create';
    private EntityManagerInterface $entityManager;
    private AgreementLineRepository $agreementLineRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        AgreementLineRepository $agreementLineRepository,
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->agreementLineRepository = $agreementLineRepository;
    }

    protected function configure()
    {
        $this->setDescription('Creates missing dpt06 tasks (intorex)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $qb = $this->agreementLineRepository->createQueryBuilder('al');
        $qb->where('al.productionStartDate IS NOT NULL')
            ->leftJoin('al.productions', 'p', 'WITH', 'p.departmentSlug = :departmentSlug')
            ->andWhere('p.id IS NULL')
            ->setParameter('departmentSlug', Department::DPT06)
            ->select('al.id');

        $ids = array_column($qb->getQuery()->getResult(), 'id');
        $totalCount = count($ids);

        $output->writeln(['', 'dpt06 (intorex) tasks creation', '========================']);
        $output->writeln(sprintf('Found %d agreement lines to process', $totalCount));

        if ($totalCount === 0) {
            $output->writeln('No lines to process.');
            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, $totalCount);
        $progressBar->start();

        $chunkSize = 100;
        $chunks = array_chunk($ids, $chunkSize);

        $department = Department::getDepartmentBySlug(Department::DPT06);

        foreach ($chunks as $chunkIds) {
            $lines = $this->agreementLineRepository->findBy(['id' => $chunkIds]);

            foreach ($lines as $line) {
                $production = new Production();
                $production
                    ->setAgreementLine($line)
                    ->setTitle($department['name'])
                    ->setDepartmentSlug($department['slug'])
                    ->setStatus(TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE)
                    ->setCreatedAt(new \DateTime())
                    ->setUpdatedAt(new \DateTime())
                ;

                $this->entityManager->persist($production);
                $progressBar->advance();
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln(sprintf('Successfully created %d production tasks', $totalCount));

        return Command::SUCCESS;
    }
}
