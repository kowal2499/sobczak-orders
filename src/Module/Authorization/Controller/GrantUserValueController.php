<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use App\Module\Authorization\Entity\AuthUserGrantValue;
use App\Module\Authorization\Repository\AuthGrantRepository;
use App\Module\Authorization\Repository\AuthUserGrantValueRepository;
use App\Module\Authorization\Service\AuthCacheService;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/grant/user/value', name: 'authorization_grant_user_value')]
class GrantUserValueController extends BaseController
{
    public function add(
        Request $request,
        UserRepository $userRepository,
        AuthGrantRepository $authGrantRepository,
        AuthUserGrantValueRepository $authUserGrantValueRepository,
        AuthCacheService $cacheService,
    ) {
        $userId = $request->request->getInt('userId');
        $grantId = $request->request->getInt('grantId');

        $user = $userRepository->find($userId);
        $grant = $authGrantRepository->find($grantId);
        if (!$user || !$grant) {
            return new JsonResponse(
                ['error' => 'Role or User not found'],
                400
            );
        }
        $grantOptionSlug = $request->request->getAlpha('optionSlug',null);
        $instance = $authUserGrantValueRepository->findOneByUserAndGrant($user, $grant, $grantOptionSlug);
        if (!$instance) {
            $instance = new AuthUserGrantValue($user, $grant, $grantOptionSlug);
        }
        $instance->setValue($request->request->getBoolean('value'));
        $authUserGrantValueRepository->add($instance);

        // clear caches
        $cacheService->invalidateAll();
        return new JsonResponse(['success' => true, 'id' => $instance->getId()]);

    }
    #[Route(path: '/{userId}',  methods: ['GET'])]
    public function read(): JsonResponse
    {
        // get listing of user's grants
        return new JsonResponse([]);
    }

    #[Route(path: '/{userId}/{grantValue}/', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // assign grant to user
        return new JsonResponse([]);
    }

    #[Route(path: '/{userId}/{grantId}', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        // remove user-grant assignment
        return new JsonResponse([]);
    }
}