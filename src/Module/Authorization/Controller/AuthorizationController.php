<?php

namespace App\Module\Authorization\Controller;

use App\Controller\BaseController;
use App\Entity\User;
use App\Module\Authorization\Service\GrantsResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class AuthorizationController extends BaseController
{

    #[Route(path: '/grants', options: ['expose' => true], methods: ['GET'])]
    public function grants(Security $security, GrantsResolver $grantsResolver): JsonResponse
    {
        $user = $security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Authenticated user is not an instance of App\Entity\User.');
        }
        return $this->apiResponse(
            $grantsResolver->getGrants($user)
        );
    }
}
