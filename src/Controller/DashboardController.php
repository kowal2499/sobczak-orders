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
     * @Route("/", name="dashboard_show")
     * @param TranslatorInterface $t
     * @return Response
     */
    public function index(TranslatorInterface $t)
    {
        return $this->render('dashboard/show.html.twig', [
            'title' => $t->trans('Pulpit', [], 'dashboard')
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