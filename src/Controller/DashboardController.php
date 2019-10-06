<?php

namespace App\Controller;

use App\Repository\AgreementLineRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;


class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="dashboard_show")
     */
    public function index()
    {
        return $this->render('dashboard/show.html.twig', [
            'title' => 'Pulpit'
        ]);
    }

    /**
     * @Route("/fetch_orders_count", name="api_fetch_orders_count", methods={"POST"}, options={"expose"=true})
     * @param AgreementLineRepository $repository
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function ApiFetchOrdersCount(AgreementLineRepository $repository)
    {
        return $this->json($repository->getSummary());
    }

}