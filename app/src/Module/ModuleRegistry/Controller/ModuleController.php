<?php

namespace App\Module\ModuleRegistry\Controller;

use App\Controller\BaseController;
use App\Module\ModuleRegistry\Repository\ModuleRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ModuleController extends BaseController
{
    #[Route(path: 'list', name: 'list', methods: ['GET'])]
    public function list(ModuleRepository $moduleRepository): JsonResponse
    {
        $data = $moduleRepository->findAllAsArray();
        return new JsonResponse($data);
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