<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use App\Module\Authorization\Repository\AuthRoleRepository;
use App\Module\Authorization\Service\AuthCacheService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/grant/role/value', name: 'authorization_grant_role_value')]

class GrantRoleValueController extends BaseController
{

    #[Route(path: '', name: '_add', methods: ['POST'])]
    public function add(
        Request $request,
        AuthRoleGrantValueRepository $roleGrantValueRepository,
        AuthRoleRepository $roleRepository,
        AuthGrantRepository $grantRepository,
        AuthCacheService $cacheService,
    ): JsonResponse
    {
        $data = $request->request->all();
        $role = $roleRepository->find($data['roleId']);
        $grant = $grantRepository->find($data['grantId']);
        if (!$role || !$grant) {
            return new JsonResponse(
                ['error' => 'Role or Grant not found'],
                400
            );
        }
        $grantOptionSlug = $data['optionSlug'] ?? null;
        $instance = $roleGrantValueRepository->findOneByRoleAndGrant($role, $grant, $grantOptionSlug);
        if (!$instance) {
            $instance = new AuthRoleGrantValue($role, $grant, $grantOptionSlug);
        }
        $instance->setValue($data['value'] ?? false);
        $roleGrantValueRepository->add($instance);

        // clear caches
        $cacheService->invalidateAll();
        return new JsonResponse(['success' => true, 'id' => $instance->getId()]);
    }

    #[Route(path: '', name: '_list', methods: ['GET'])]
    public function list(AuthRoleGrantValueRepository $roleGrantValueRepository): JsonResponse
    {
        $all = $roleGrantValueRepository->findAll();
        $result = array_map(function ($item) {
            return [
                'id' => $item->getId(),
                'role_id' => $item->getRole()->getId(),
                'grant_id' => $item->getGrant()->getId(),
                'option_slug' => $item->getGrantOptionSlug(),
                'value' => $item->getValue(),
            ];
        }, $all);

        return new JsonResponse($result);
    }
}