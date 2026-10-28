<?php

namespace App\Module\Spa\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SpaController extends AbstractController
{
    #[Route(path: '/{path}', name: 'spa_shell', requirements: ['path' => '.*'], defaults: ['path' => ''], methods: ['GET'], priority: -1000)]
    public function shell(Request $request): Response
    {
        // There is no /api prefix, so this is what keeps a mistyped XHR URL a 404 instead of a 200 with HTML.
        if (!in_array('text/html', $request->getAcceptableContentTypes(), true)) {
            throw new NotFoundHttpException();
        }

        return $this->render('spa.html.twig');
    }
}
