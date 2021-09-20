<?php

namespace App\MessageHandler\AgreementLine;

use App\Entity\AgreementLine;
use App\Message\AgreementLine\UpdateProductionStartDate;
use App\Repository\AgreementLineRepository;
use App\Service\AgreementLine\ProductionStartDateResolverService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UpdateProductionStartDateHandler implements MessageHandlerInterface
{
    /** @var AgreementLineRepository $agreementLineRepository */
    private $agreementLineRepository;
    /** @var EntityManagerInterface */
    private $entityManager;
    private $startDateResolverService;

    public function __construct(EntityManagerInterface $entityManager, ProductionStartDateResolverService $startDateResolverService)
    {
        $this->entityManager = $entityManager;
        $this->agreementLineRepository = $this->entityManager->getRepository(AgreementLine::class);
        $this->startDateResolverService = $startDateResolverService;
    }

    public function __invoke(UpdateProductionStartDate $command)
    {
        /** @var AgreementLine $agreementLine */
        $agreementLine = $this->agreementLineRepository->findWithProductionAndStatuses($command->getAgreementLineId());

        if (!$agreementLine) {
            throw new \RuntimeException('AgreementLine not found');
        }
        $this->entityManager->refresh($agreementLine);

        $earliestStartDate = $this->startDateResolverService->getStartDate($agreementLine->getProductions());

        if ($earliestStartDate != $agreementLine->getProductionStartDate()) {
            $agreementLine->setProductionStartDate($earliestStartDate);
            $this->entityManager->flush();
        }
    }
}