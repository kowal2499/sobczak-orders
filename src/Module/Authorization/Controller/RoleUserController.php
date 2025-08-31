<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use App\Entity\User;
use App\Module\Authorization\Entity\AuthUserRole;
use App\Module\Authorization\Repository\AuthRoleRepository;
use App\Module\Authorization\Repository\AuthUserRoleRepository;
use App\Module\Authorization\Service\AuthCacheService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/role/user', name: 'authorization_role_user')]
class RoleUserController extends BaseController
{
    #[Route(path: '/{user}',  methods: ['GET'])]
    public function read(User $user, AuthUserRoleRepository $roleRepository): JsonResponse
    {
        return new JsonResponse(
            array_map(function (AuthUserRole $ur) {
                return [
                    'id' => $ur->getRole()->getId(),
                    'name' => $ur->getRole()->getName()
                ];
            }, $roleRepository->findAllByUser($user))
        );
    }

    #[Route(path: '/{user}/assign', methods: ['POST'])]
    public function assign(
        Request $request,
        User $user,
        AuthUserRoleRepository $userRoleRepository,
        AuthRoleRepository $roleRepository,
        AuthCacheService $cacheService,
    ): JsonResponse
    {
        $newRoleIds = $request->request->all('roles', []);
        if (!is_array($newRoleIds)) {
            return new JsonResponse(['error' => 'Invalid roles'], 400);
        }

        foreach ($userRoleRepository->findAllByUser($user) as $userRole) {
            if (!in_array($userRole->getRole()->getId(), $newRoleIds)) {
                $userRoleRepository->remove($userRole, true);
            } else {
                // role already assigned, remove from new roles to avoid re-adding
                $newRoleIds = array_diff($newRoleIds, [$userRole->getRole()->getId()]);
            }
        }

        foreach ($newRoleIds as $newRoleId) {
            $role = $roleRepository->find($newRoleId);
            if (!$role) {
                return new JsonResponse(['error' => 'Invalid role id:' . $newRoleId ], 400);
            }
            $userRole = new AuthUserRole($user, $role);
            $userRoleRepository->add($userRole);
        }

        $cacheService->invalidateAll();

        return new JsonResponse();
    }
}