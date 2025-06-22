<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/auth/role/user', name: 'authorization')]
class RoleUserController extends BaseController
{
    #[Route(path: '/{userId}',  methods: ['GET'])]
    public function read(): JsonResponse
    {
        // get listing of user's roles
        return new JsonResponse([]);
    }

    #[Route(path: '/{userId}/{roleId}/', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // assign role to user
        return new JsonResponse([]);
    }

    #[Route(path: '/{userId}/{roleId}', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        // remove user-role assignment
        return new JsonResponse([]);
    }
}