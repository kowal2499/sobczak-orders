<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/auth/grant/role', name: 'authorization')]
class GrantRoleController extends BaseController
{
    #[Route(path: '/{roleId}',  methods: ['GET'])]
    public function read(): JsonResponse
    {
        // get listing of role's grants
        return new JsonResponse([]);
    }

    #[Route(path: '/{roleId}/{grantValue}/', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // assign grant to role
        return new JsonResponse([]);
    }

    #[Route(path: '/{grantId}/{roleId}', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        // remove grant-role assignment
        return new JsonResponse([]);
    }
}