<?php

namespace App\Module\Task\Controller;

use App\Controller\BaseController;
use App\Module\Task\Command\CreateTaskCommand;
use App\Module\Task\Command\DeleteTaskCommand;
use App\Module\Task\Command\UpdateTaskCommand;
use App\System\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

#[Route('/tasks')]
class TaskController extends BaseController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly Security $security,
    ) {
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Walidacja danych wejściowych
        if (!isset($data['agreementLineId']) || !is_numeric($data['agreementLineId']) || $data['agreementLineId'] <= 0) {
            return $this->json(['error' => 'agreementLineId is required and must be a positive integer'], Response::HTTP_BAD_REQUEST);
        }


        if (!isset($data['status']) || !in_array($data['status'], [10, 11, 12])) {
            return $this->json(['error' => 'status is required and must be one of: 10, 11, 12'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['type']) || !in_array($data['type'], ['task_custom', 'task_confirm_realization_date'])) {
            return $this->json(['error' => 'type is required and must be one of: task_custom, task_confirm_realization_date'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new CreateTaskCommand(
                agreementLineId: (int) $data['agreementLineId'],
                dateStart: $data['dateStart'] ?? null,
                dateEnd: $data['dateEnd'] ?? null,
                status: (int) $data['status'],
                type: $data['type'],
                title: $data['title'] ?? null,
                description: $data['description'] ?? null,
                ownerId: isset($data['ownerId']) && is_numeric($data['ownerId']) ? (int) $data['ownerId'] : null,
            );

            $this->commandBus->dispatch($command);

            return $this->json(['success' => true, 'message' => 'Task created successfully'], Response::HTTP_CREATED);
        } catch (\Symfony\Component\Messenger\Exception\HandlerFailedException $e) {
            // Unwrap the nested exception from Messenger
            $nested = $e->getNestedExceptions()[0] ?? null;
            if ($nested instanceof \InvalidArgumentException) {
                return $this->json(['error' => $nested->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            return $this->json(['error' => 'An unexpected error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An unexpected error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Walidacja danych wejściowych

        if (!isset($data['status']) || !in_array($data['status'], [10, 11, 12])) {
            return $this->json(['error' => 'status is required and must be one of: 10, 11, 12'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new UpdateTaskCommand(
                taskId: $id,
                userId: $user->getId(),
                dateStart: $data['dateStart'] ?? null,
                dateEnd: $data['dateEnd'] ?? null,
                status: (int) $data['status'],
                title: $data['title'] ?? null,
                description: $data['description'] ?? null,
            );

            $this->commandBus->dispatch($command);

            return $this->json(['success' => true, 'message' => 'Task updated successfully'], Response::HTTP_OK);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Symfony\Component\Messenger\Exception\HandlerFailedException $e) {
            // Unwrap the nested exception from Messenger
            $nested = $e->getNestedExceptions()[0] ?? null;
            if ($nested instanceof AccessDeniedException) {
                return $this->json(['error' => $nested->getMessage()], Response::HTTP_FORBIDDEN);
            }
            if ($nested instanceof \InvalidArgumentException) {
                return $this->json(['error' => $nested->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            return $this->json(['error' => 'An unexpected error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An unexpected error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $command = new DeleteTaskCommand(
                taskId: $id,
                userId: $user->getId(),
            );

            $this->commandBus->dispatch($command);

            return $this->json(['success' => true, 'message' => 'Task deleted successfully'], Response::HTTP_OK);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Symfony\Component\Messenger\Exception\HandlerFailedException $e) {
            // Unwrap the nested exception from Messenger
            $nested = $e->getNestedExceptions()[0] ?? null;
            if ($nested instanceof AccessDeniedException) {
                return $this->json(['error' => $nested->getMessage()], Response::HTTP_FORBIDDEN);
            }
            if ($nested instanceof \InvalidArgumentException) {
                return $this->json(['error' => $nested->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            return $this->json(['error' => 'An unexpected error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return $this->json(['error' => 'An unexpected error occurred'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
