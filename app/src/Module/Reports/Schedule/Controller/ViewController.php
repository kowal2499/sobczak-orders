<?php

namespace App\Module\Reports\Schedule\Controller;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ViewController extends BaseController
{
    #[Route(path: '/view/production/v2', name: 'schedule_production_v2', methods: ['GET'])]
    public function calendarV2(): Response
    {
        return $this->render('schedule/schedule_production_v2.html.twig');
    }

    #[Route(path: '/view/production', name: 'schedule_production', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('schedule/schedule_production.html.twig');
    }
}