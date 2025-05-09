<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AccountController extends AbstractController
{
    #[Route(path: '/account', name: 'account')]
    public function index(): Response
    {
        return $this->render('account/index.html.twig', [
            'title' => 'Użytkownicy'
        ]);
    }
}
