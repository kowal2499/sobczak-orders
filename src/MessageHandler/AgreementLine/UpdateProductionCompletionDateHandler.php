<?php

namespace App\MessageHandler\AgreementLine;

use App\Entity\AgreementLine;
use App\Message\AgreementLine\UpdateProductionCompletionDate;
use App\Service\AgreementLine\ProductionCompletionDateResolverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UpdateProductionCompletionDateHandler implements MessageHandlerInterface
{
    private $lineRepository;
    private $entityManager;
    private $dateResolverService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductionCompletionDateResolverService $dateResolverService
    ) {
        $this->lineRepository = $entityManager->getRepository(AgreementLine::class);
        $this->entityManager = $entityManager;
        $this->dateResolverService = $dateResolverService;
    }

    public function __invoke(UpdateProductionCompletionDate $command)
    {
        /** @var AgreementLine $agreementLine */
        $agreementLine = $this->lineRepository->findWithProductionAndStatuses($command->getAgreementLineId());

        if (!$agreementLine) {
            throw new \RuntimeException('AgreementLine not found');
        }
        $completionDate = $this->dateResolverService->getCompletionDate($agreementLine->getProductions());

        if ($completionDate != $agreementLine->getProductionCompletionDate()) {
            $agreementLine->setProductionCompletionDate($completionDate);
            $this->entityManager->flush();
        }
    }
}