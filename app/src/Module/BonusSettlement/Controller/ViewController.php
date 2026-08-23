<?php

namespace App\Module\BonusSettlement\Controller;

use App\Controller\BaseController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ViewController extends BaseController
{
    #[Route(path: '/bonus-settlement', name: 'bonus_settlement_view', methods: ['GET'])]
    #[IsGranted('bonus-settlement.view')]
    public function index(): Response
    {
        return $this->render('bonus_settlement/index.html.twig');
    }
}
