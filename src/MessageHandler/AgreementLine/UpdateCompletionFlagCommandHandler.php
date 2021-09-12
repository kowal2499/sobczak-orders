<?php

namespace App\MessageHandler\AgreementLine;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Message\AgreementLine\UpdateCompletionFlagCommand;
use App\Repository\AgreementLineRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

class UpdateCompletionFlagCommandHandler
{
    private $lineRepository;
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->lineRepository = $entityManager->getRepository(AgreementLine::class);
        $this->entityManager = $entityManager;
    }

    public function __invoke(UpdateCompletionFlagCommand $command)
    {
        /** @var AgreementLine $agreementLine */
        $agreementLine = $this->lineRepository->findWithProductionAndStatuses($command->getAgreementLineId());

        $completionDate = null;
        foreach ($agreementLine->getProductions() as $production) {
            if ($production->getDepartmentSlug() === TaskTypes::TYPE_DEFAULT_SLUG_PACKAGING) {
                foreach ($production->getStatusLogs() as $log) {
                    $completionDate = ($log->getCurrentStatus() == TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED)
                        ? $log->getCreatedAt()
                        : null;
                }
            }
        }
        $agreementLine->setProductionCompletionDate($completionDate);
        $this->entityManager->persist($agreementLine);
    }
}