<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use App\Module\Authorization\Repository\AuthRoleRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/role', name: 'authorization_role')]
class RoleController extends BaseController
{
    #[Route(path: '/list', name: 'list', methods: ['GET'])]
    public function list(AuthRoleRepository $authRoleRepository): JsonResponse
    {
        return new JsonResponse($authRoleRepository->findAllAsArray());
    }

    #[Route(path: '', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // create new role
        return new JsonResponse([]);
    }

    #[Route(path: '/{roleId}', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        // update a role
        return new JsonResponse([]);
    }


    #[Route(path: '/{roleId}', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        // delete a role
        return new JsonResponse([]);
    }

    #[Route(path: '/view',  name: '_role_view', methods: ['GET'])]
    public function view(): Response
    {
        return $this->render('authorization/role.html.twig');
    }
}