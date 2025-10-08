<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use App\Module\Authorization\Command\CreateRoleGrantValue;
use App\Module\Authorization\Command\DeleteRoleGrantValue;
use App\Module\Authorization\Entity\AuthRole;
use App\Module\Authorization\Entity\AuthRoleGrantValue;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthRoleGrantValueRepository;
use App\Module\Authorization\Repository\AuthRoleRepository;
use App\Module\Authorization\Service\AuthCacheService;
use App\System\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


class GrantRoleValueController extends BaseController
{
    #[Route(path: '/grant/role/{role}/value', name: 'authorization_grant_role_value_add', methods: ['POST'])]
    public function store(
        Request $request,
        AuthRole $role,
        CommandBus $commandBus,
    ): JsonResponse
    {
        foreach ($request->request->all() as $grantRoleValue) {
            if (!array_key_exists('grant_id', $grantRoleValue) ||
                !array_key_exists('grant_option_slug', $grantRoleValue) ||
                !array_key_exists('value', $grantRoleValue)
            ) {
                return new JsonResponse(
                    ['error' => 'Invalid input data'],
                    400
                );
            }

            if ($grantRoleValue['value']) {
                $command = new CreateRoleGrantValue($role->getId(), $grantRoleValue['grant_id'], $grantRoleValue['grant_option_slug'], true);
            } else {
                $command = new DeleteRoleGrantValue($role->getId(), $grantRoleValue['grant_id'], $grantRoleValue['grant_option_slug']);
            }

            $commandBus->dispatch($command);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/grant/role/value', name: 'authorization_grant_role_value_list', methods: ['GET'])]
    public function list(AuthRoleGrantValueRepository $roleGrantValueRepository): JsonResponse
    {
        $all = $roleGrantValueRepository->findAll();
        $result = array_map(function ($item) {
            return [
                'id' => $item->getId(),
                'role_id' => $item->getRole()->getId(),
                'grant_id' => $item->getGrant()->getId(),
                'grant_option_slug' => $item->getGrantOptionSlug(),
                'value' => $item->getValue(),
            ];
        }, $all);

        return new JsonResponse($result);
    }
}