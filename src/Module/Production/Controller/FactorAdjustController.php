<?php

namespace App\Module\Production\Controller;

use App\Controller\BaseController;
use App\Entity\Production;
use App\Module\Production\Entity\FactorAdjust;
use App\Module\Production\Repository\FactorAdjustRepository;
use App\Module\Production\Repository\Interface\FactorAdjustRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/factor-adjust', name: 'production_factor_adjust')]

class FactorAdjustController extends BaseController
{
    #[Route(path: '/create/{production}',  methods: ['POST'])]
    public function create(
        Request $request,
        Production $production,
        FactorAdjustRepositoryInterface $factorAdjustRepository
    ): JsonResponse
    {
        $description = $request->request->get('description');
        $factor = (float)$request->request->get('factor');

        if (!$description || !$factor) {
            throw new BadRequestHttpException("Description and factor are required.");
        }

        $adjust = new FactorAdjust();
        $adjust->setProduction($production);
        $adjust->setDescription($description);
        $adjust->setFactor($factor);

        $factorAdjustRepository->add($adjust);
        return $this->json(['id' => $adjust->getId()]);
    }

    #[Route(path: '/{factorAdjust}',  methods: ['GET'])]
    public function read(FactorAdjust $factorAdjust): JsonResponse
    {
        return $this->json([]);
    }

    #[Route(path: '/{factorAdjust}',  methods: ['PUT'])]
    public function update(Request $request, FactorAdjust $factorAdjust): JsonResponse
    {
        return $this->json([]);
    }

    #[Route(path: '/{factorAdjust}',  methods: ['DELETE'])]
    public function delete(FactorAdjust $factorAdjust): JsonResponse
    {
        return $this->json([]);
    }
}