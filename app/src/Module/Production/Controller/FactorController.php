<?php

namespace App\Module\Production\Controller;

use App\Controller\BaseController;
use App\Entity\AgreementLine;
use App\Module\AgreementLine\Event\AgreementLineWasUpdatedEvent;
use App\Module\Production\DTO\FactorRatioDTO;
use App\Module\Production\Entity\Factor;
use App\Module\Production\Repository\FactorRepository;
use App\Module\Production\Service\FactorWriteService;
use App\System\EventBus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/factor', name: 'production_factor')]
class FactorController extends BaseController
{
    #[Route(path: '/{agreementLine}', methods: ['POST'])]
    #[IsGranted('production.factor_adjustment')]
    public function storeFromForm(
        Request $request,
        AgreementLine $agreementLine,
        FactorWriteService $factorWriteService,
        EventBus $eventBus,
    ): JsonResponse {
        $factors = (array) $request->request->get('factors', []);

        $factorWriteService->store(
            $agreementLine->getId(),
            array_map(fn ($data) => FactorRatioDTO::fromArray($data), $factors),
        );

        $eventBus->dispatch(new AgreementLineWasUpdatedEvent($agreementLine->getId()));

        return $this->json([], Response::HTTP_OK);
    }

    #[Route(path: '/{agreementLine}', methods: ['GET'])]
    public function readAsForm(AgreementLine $agreementLine, FactorRepository $factorRepository): JsonResponse
    {
        return $this->json(array_map(fn (Factor $factor) => [
            'id' => $factor->getId(),
            'departmentSlug' => $factor->getDepartmentSlug(),
            'value' => $factor->getFactorValue(),
            'source' => $factor->getSource()->value,
            'description' => $factor->getDescription(),
        ], $factorRepository->findBy(['agreementLine' => $agreementLine])), Response::HTTP_OK);
    }
}
