<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use App\Entity\User;
use App\Module\Authorization\Command\CreateUserGrantValue;
use App\Module\Authorization\Command\DeleteUserGrantValue;
use App\Module\Authorization\Entity\AuthUserGrantValue;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthUserGrantValueRepository;
use App\Module\Authorization\Service\AuthCacheService;
use App\Module\Authorization\ValueObject\GrantType;
use App\Repository\UserRepository;
use App\System\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GrantUserValueController extends BaseController
{
    #[Route(path: '/grant/user/{user}/values', name: 'authorization_grant_user_value_store', methods: ['POST'])]
    public function store(
        Request $request,
        User $user,
        AuthUserGrantValueRepository $authUserGrantValueRepository,
        CommandBus $commandBus,
    ) {
        $existingValues = $authUserGrantValueRepository->findAllByUser($user);

        foreach ($request->request->all() as $grantUserValue) {
            if (!array_key_exists('user_id', $grantUserValue) ||
                !array_key_exists('grant_id', $grantUserValue) ||
                !array_key_exists('grant_option_slug', $grantUserValue) ||
                !array_key_exists('value', $grantUserValue)
            ) {
                return new JsonResponse(
                    ['error' => 'Invalid input data'],
                    400
                );
            }

            $commandBus->dispatch(new CreateUserGrantValue(
                $user->getId(),
                (int)$grantUserValue['grant_id'],
                $grantUserValue['grant_option_slug'] ?: null,
                (bool)$grantUserValue['value']
            ));

            // Remove from existing values to avoid deletion
            foreach ($existingValues as $key => $existingValue) {
                if ($existingValue->getGrant()->getId() === $grantUserValue['grant_id'] &&
                    $existingValue->getGrantOptionSlug() === $grantUserValue['grant_option_slug']
                ) {
                    unset($existingValues[$key]);
                    break;
                }
            }
        }

        // Delete remaining existing values that were not in the request
        foreach ($existingValues as $existingValue) {
            $commandBus->dispatch(
                new DeleteUserGrantValue($existingValue->getId())
            );
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/grant/user/value/{user}', name: 'authorization_grant_user_value_list',  methods: ['GET'])]
    public function list(
        User $user,
        AuthUserGrantValueRepository $authUserGrantValueRepository
    ): JsonResponse
    {
        $result = array_map(function (AuthUserGrantValue $item) {
            return [
                'id' => $item->getId(),
                'user_id' => $item->getUser()->getId(),
                'grant_id' => $item->getGrant()->getId(),
                'grant_option_slug' => $item->getGrantOptionSlug(),
                'value' => $item->getValue(),
            ];
        }, $authUserGrantValueRepository->findAllByUser($user));

        return $this->json($result);
    }
}