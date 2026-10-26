<?php

namespace App\Module\MetricSnapshot\Controller;

use App\Controller\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ViewController extends BaseController
{
    #[Route(path: '/metric-history', name: 'metric_history_view', methods: ['GET'])]
    #[IsGranted('metric-snapshot.view')]
    public function index(): Response
    {
        return $this->render('metric_history/index.html.twig');
    }
}
