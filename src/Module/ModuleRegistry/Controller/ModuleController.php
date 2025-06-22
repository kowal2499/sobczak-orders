<?php

namespace App\Module\ModuleRegistry\Controller;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/module')]
class ModuleController extends BaseController
{
    #[Route(path: 'list', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        // get listing of all modules
        return new JsonResponse([]);
    }

    #[Route(path: '', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // create new module
        return new JsonResponse([]);
    }

    #[Route(path: '{moduleId}', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        // update a module
        return new JsonResponse([]);
    }


    #[Route(path: '{moduleId}', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        // delete a module
        return new JsonResponse([]);
    }
}