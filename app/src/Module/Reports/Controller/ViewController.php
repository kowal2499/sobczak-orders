<?php

namespace App\Module\Reports\Controller;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ViewController extends BaseController
{
    #[Route(path: '/differences', name: 'reports_view_differences', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('reports/report_differences.html.twig');
    }
}
