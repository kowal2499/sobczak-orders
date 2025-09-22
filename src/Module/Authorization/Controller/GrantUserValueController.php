<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use App\Entity\User;
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
    #[Route(path: '', name: '_create', methods: ['POST'])]
    public function create(
        Request $request,
        UserRepository $userRepository,
        AuthGrantRepository $authGrantRepository,
        AuthUserGrantValueRepository $authUserGrantValueRepository,
        AuthCacheService $cacheService,
    ) {
        $userId = $request->request->getInt('userId');
        $grantId = $request->request->getInt('grantId');
        $grantOptionSlug = $request->request->getAlpha('optionSlug',null);

        $user = $userRepository->find($userId);
        $grant = $authGrantRepository->find($grantId);
        if (!$user || !$grant) {
            return new JsonResponse(
                ['error' => 'Role or User not found'],
                400
            );
        }

        if ($authUserGrantValueRepository->findOneByUserAndGrant($user, $grant, $grantOptionSlug)) {
            return new JsonResponse(
                ['error' => 'Such grant value already exists for this user'],
                400
            );
        }

        $instance = new AuthUserGrantValue($user, $grant, $grantOptionSlug);
        $instance->setValue($request->request->getBoolean('value'));
        $authUserGrantValueRepository->add($instance);

        // clear caches
        $cacheService->invalidateAll();
        return new JsonResponse(['success' => true, 'id' => $instance->getId()]);
    }

    #[Route(path: '/{user}', name: '_list',  methods: ['GET'])]
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
                'option_slug' => $item->getGrantOptionSlug(),
                'value' => $item->getValue(),
            ];
        }, $authUserGrantValueRepository->findAllByUser($user));

        return $this->json($result);
    }

    #[Route(path: '/{authUserGrantValue}', name: '_update', methods: ['PUT'])]
    public function update(
        Request $request,
        AuthUserGrantValue $authUserGrantValue,
        AuthUserGrantValueRepository $authUserGrantValueRepository,
        AuthCacheService $cacheService,
    ): JsonResponse
    {
        $authUserGrantValue->setValue($request->request->getBoolean('value'));
        $authUserGrantValueRepository->add($authUserGrantValue);
        $cacheService->invalidateAll();
        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/{authUserGrantValue}', name: '_delete', methods: ['DELETE'])]
    public function delete(
        AuthUserGrantValue $authUserGrantValue,
        AuthUserGrantValueRepository $authUserGrantValueRepository,
        AuthCacheService $cacheService,
    ): JsonResponse
    {
        $authUserGrantValueRepository->remove($authUserGrantValue);
        $cacheService->invalidateAll();
        return new JsonResponse(['success' => true]);
    }
}