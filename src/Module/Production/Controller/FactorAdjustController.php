<?php

namespace App\Module\Production\Controller;

use App\Controller\BaseController;
use App\Entity\Production;
use App\Module\Production\Command\CreateFactorAdjust;
use App\Module\Production\Command\DeleteFactorAdjust;
use App\Module\Production\Command\UpdateFactorAdjust;
use App\Module\Production\Entity\FactorAdjust;
use App\System\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route(path: '/factor-adjust', name: 'production_factor_adjust')]

class FactorAdjustController extends BaseController
{
    #[Route(path: '/create/{production}',  methods: ['POST'])]
    #[IsGranted('production.factor_adjustment:create')]
    public function create(
        Request $request,
        Production $production,
        CommandBus $commandBus,
    ): JsonResponse
    {
        $description = $request->request->get('description');
        $factor = (float)$request->request->get('factor');
        if (!$description || !$factor) {
            throw new BadRequestHttpException("Description and factor are required.");
        }

        $commandBus->dispatch(new CreateFactorAdjust($production->getId(), $description, $factor));
        return $this->json([], Response::HTTP_OK);
    }

    #[Route(path: '/{factorAdjust}',  methods: ['GET'])]
    #[IsGranted('production.factor_adjustment:read')]
    public function read(FactorAdjust $factorAdjust): JsonResponse
    {
        return $this->json([
            'id' => $factorAdjust->getId(),
            'productionId' => $factorAdjust->getProduction()->getId(),
            'description' => $factorAdjust->getDescription(),
            'factor' => $factorAdjust->getFactor(),
        ]);
    }

    #[Route(path: '/{factorAdjust}',  methods: ['PUT'])]
    #[IsGranted('production.factor_adjustment:update')]
    public function update(
        Request $request,
        FactorAdjust $factorAdjust,
        CommandBus $commandBus,
    ): JsonResponse
    {
        $description = $request->request->get('description');
        $factor = (float)$request->request->get('factor');
        if (!$description || !$factor) {
            throw new BadRequestHttpException("Description and factor are required.");
        }

        $commandBus->dispatch(new UpdateFactorAdjust($factorAdjust->getId(), $description, $factor));
        return $this->json([], Response::HTTP_OK);
    }

    #[Route(path: '/{factorAdjust}', methods: ['DELETE'])]
    #[IsGranted('production.factor_adjustment:delete')]
    public function delete(
        FactorAdjust $factorAdjust,
        CommandBus $commandBus
    ): JsonResponse
    {
        $commandBus->dispatch(new DeleteFactorAdjust($factorAdjust->getId()));
        return $this->json([], Response::HTTP_OK);
    }
}