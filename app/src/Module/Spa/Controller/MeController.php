<?php

namespace App\Module\Spa\Controller;

use App\Controller\BaseController;
use App\Entity\User;
use App\Module\Spa\Query\GetCurrentUserQuery;
use App\System\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MeController extends BaseController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route(path: '/me', name: 'spa_me', options: ['expose' => true], methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Authenticated user is not an instance of App\Entity\User.');
        }

        return $this->json($this->queryBus->query(new GetCurrentUserQuery(
            $user,
            $request->getLocale(),
            $this->isGranted('IS_IMPERSONATOR'),
        )));
    }
}
