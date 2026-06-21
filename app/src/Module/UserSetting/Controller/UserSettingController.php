<?php

namespace App\Module\UserSetting\Controller;

use App\Controller\BaseController;
use App\Module\UserSetting\Command\SaveUserSettingCommand;
use App\Module\UserSetting\Query\GetUserSettingQuery;
use App\System\CommandBus;
use App\System\QueryBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/user-settings')]
class UserSettingController extends BaseController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly Security $security,
    ) {
    }

    #[Route('/{context}', methods: ['GET'])]
    public function get(string $context): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->queryBus->query(new GetUserSettingQuery($user->getId(), $context));

        return $this->json(['data' => $data]);
    }

    #[Route('/{context}', methods: ['PUT'])]
    public function save(string $context, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $body = json_decode($request->getContent(), true);

        if (!isset($body['data']) || !is_array($body['data'])) {
            return $this->json(['error' => 'data is required and must be an object/array'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new SaveUserSettingCommand(
                userId: $user->getId(),
                context: $context,
                data: $body['data'],
            );

            $this->commandBus->dispatch($command);

            return $this->json(['success' => true], Response::HTTP_OK);
        } catch (\Symfony\Component\Messenger\Exception\HandlerFailedException $e) {
            $nested = $e->getNestedExceptions()[0] ?? null;
            if ($nested instanceof \InvalidArgumentException) {
                return $this->json(['error' => $nested->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            return $this->json(['error' => 'An unexpected error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
