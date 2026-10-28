<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProductRepository;

class ProductsController extends AbstractController
{
    /**
     * @param Request $request
     * @param ProductRepository $repository
     * @return JsonResponse
     */
    #[Route(path: '/products/fetch', name: 'products_fetch', options: ['expose' => true])]
    public function fetch(Request $request, ProductRepository $repository): JsonResponse
    {
        $response = [
            'products' => $repository->getByName()->getArrayResult(),
        ];
        return new JsonResponse($response);
    }
}
