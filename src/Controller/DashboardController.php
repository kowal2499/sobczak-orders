<?php

namespace App\Controller;

use App\Repository\AgreementLineRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;


class DashboardController extends AbstractController
{
    /**
     * @param TranslatorInterface $t
     * @return Response
     */
    #[Route(path: '/', name: 'dashboard_show')]
    public function index(TranslatorInterface $t): Response
    {
        return $this->render('dashboard/show.html.twig', [
            'title' => $t->trans('Pulpit', [], 'dashboard')
        ]);
    }

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