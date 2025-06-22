<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/auth/grant/user', name: 'authorization')]
class GrantUserController extends BaseController
{
    #[Route(path: '/{userId}',  methods: ['GET'])]
    public function read(): JsonResponse
    {
        // get listing of user's grants
        return new JsonResponse([]);
    }

    #[Route(path: '/{userId}/{grantValue}/', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // assign grant to user
        return new JsonResponse([]);
    }

    #[Route(path: '/{userId}/{grantId}', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        // remove user-grant assignment
        return new JsonResponse([]);
    }
}