<?php

namespace App\Controller;

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

}