<?php

namespace App\Controller;

use App\Repository\AgreementLineRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class DashboardController extends AbstractController
{
    /**
     * @param AgreementLineRepository $repository
     * @return JsonResponse
     */
    #[Route(path: '/fetch_orders_count', name: 'api_fetch_orders_count', options: ['expose' => true], methods: ['POST'])]
    public function ApiFetchOrdersCount(AgreementLineRepository $repository): JsonResponse
    {
        return $this->json($repository->getSummary());
    }

}