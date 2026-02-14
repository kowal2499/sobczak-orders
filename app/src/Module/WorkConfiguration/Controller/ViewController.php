<?php

namespace App\Module\WorkConfiguration\Controller;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(name: 'work_configuration_view')]

class ViewController extends BaseController
{
    #[Route(path: '', name: '', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('configuration/work_configuration.html.twig');
    }
}
