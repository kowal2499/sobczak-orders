<?php

namespace App\Module\Production\Controller;

use App\Module\Production\Service\ProductionDateStrategy\ProductionDateStrategyResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DateStrategyController extends AbstractController
{
    #[Route(path: '/date-strategies', methods: ['GET'])]
    public function list(ProductionDateStrategyResolver $resolver): JsonResponse
    {
        $definitions = array_map(
            fn($strategy) => $strategy->getDefinition(),
            $resolver->getAll()
        );

        return $this->json($definitions);
    }
}
