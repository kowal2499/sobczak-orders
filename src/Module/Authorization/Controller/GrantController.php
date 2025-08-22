<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use App\Module\Authorization\Repository\AuthGrantRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/grant', name: 'authorization_grant')]
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

    #[Route(path: '/list', methods: ['GET'])]
    public function index(AuthGrantRepository $grantRepository): JsonResponse
    {
        $data = $grantRepository->findAllAsArray();
        return new JsonResponse($data);
    }
}