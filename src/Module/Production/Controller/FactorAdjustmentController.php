<?php

namespace App\Module\Production\Controller;

use App\Controller\BaseController;
use App\Entity\Production;
use App\Module\Production\Command\CreateFactorAdjustment;
use App\Module\Production\Command\DeleteFactorAdjustment;
use App\Module\Production\Command\UpdateFactorAdjustment;
use App\Module\Production\Entity\FactorAdjustment;
use App\System\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[Route(path: '/factor-adjustment', name: 'production_factor_adjustment')]

class FactorAdjustmentController extends BaseController
{
    #[Route(path: '/create/{production}',  methods: ['POST'])]
    #[IsGranted('production.factor_adjustment_bonus:create')]
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

        $commandBus->dispatch(new CreateFactorAdjustment($production->getId(), $description, $factor));
        return $this->json([], Response::HTTP_OK);
    }

    #[Route(path: '/{factorAdjust}',  methods: ['GET'])]
    #[IsGranted('production.factor_adjustment_bonus:read')]
    public function read(FactorAdjustment $factorAdjust): JsonResponse
    {
        return $this->json([
            'id' => $factorAdjust->getId(),
            'productionId' => $factorAdjust->getProduction()->getId(),
            'description' => $factorAdjust->getDescription(),
            'factor' => $factorAdjust->getFactor(),
        ]);
    }

    #[Route(path: '/{factorAdjust}',  methods: ['PUT'])]
    #[IsGranted('production.factor_adjustment_bonus:update')]
    public function update(
        Request          $request,
        FactorAdjustment $factorAdjust,
        CommandBus       $commandBus,
    ): JsonResponse
    {
        $description = $request->request->get('description');
        $factor = (float)$request->request->get('factor');
        if (!$description || !$factor) {
            throw new BadRequestHttpException("Description and factor are required.");
        }

        $commandBus->dispatch(new UpdateFactorAdjustment($factorAdjust->getId(), $description, $factor));
        return $this->json([], Response::HTTP_OK);
    }

    #[Route(path: '/{factorAdjust}', methods: ['DELETE'])]
    #[IsGranted('production.factor_adjustment_bonus:update')]
    public function delete(
        FactorAdjustment $factorAdjust,
        CommandBus       $commandBus
    ): JsonResponse
    {
        $commandBus->dispatch(new DeleteFactorAdjustment($factorAdjust->getId()));
        return $this->json([], Response::HTTP_OK);
    }
}