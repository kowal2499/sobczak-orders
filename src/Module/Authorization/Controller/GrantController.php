<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/auth/grant', name: 'authorization')]
class GrantController extends BaseController
{
    #[Route(path: '', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // create new grant
        return new JsonResponse([]);
    }

    #[Route(path: '{id}', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        // update a grant
        return new JsonResponse([]);
    }


    #[Route(path: '{id}', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        // delete a grant
        return new JsonResponse([]);
    }
}