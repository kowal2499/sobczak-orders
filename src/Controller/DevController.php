<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DevController extends BaseController
{
    #[Route(path: '/dev', name: 'dev')]
    #[IsGranted('authorization.admin')]
    public function dev(): Response
    {
        return $this->render('dev/index.html.twig', []);
    }
}