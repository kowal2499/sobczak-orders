<?php

namespace App\Module\Task\Controller;

use App\Controller\BaseController;
use App\Module\Task\Command\CreateTaskCommand;
use App\Module\Task\Command\DeleteTaskCommand;
use App\Module\Task\Command\UpdateTaskCommand;
use App\Module\Task\Entity\Task;
use App\Module\Task\DTO\TaskDTO;
use App\Module\Task\Repository\TaskRepository;
use App\Module\Task\ValueObject\TaskStatusEnum;
use App\Module\Task\ValueObject\TaskTypeEnum;
use App\Repository\AgreementLineRepository;
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
        private readonly TaskRepository $taskRepository,
        private readonly AgreementLineRepository $agreementLineRepository,
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

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['PUT'])]
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

    #[Route('/{task}/status', methods: ['POST'])]
    public function updateStatus(Task $task, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        $status = isset($data['status']) ? TaskStatusEnum::tryFrom((int) $data['status']) : null;
        if ($status === null) {
            $validValues = implode(', ', array_column(TaskStatusEnum::cases(), 'value'));
            return $this->json(['error' => "status is required and must be one of: $validValues"], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new UpdateTaskCommand(
                taskId: $task->getId(),
                userId: $user->getId(),
                dateStart: $task->getDateStart()?->format('Y-m-d'),
                dateEnd: $task->getDateEnd()?->format('Y-m-d'),
                status: $status->value,
                title: $task->getTitle(),
                description: $task->getDescription(),
            );

            $this->commandBus->dispatch($command);

            return $this->json(['success' => true, 'message' => 'Task status updated successfully'], Response::HTTP_OK);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (\Symfony\Component\Messenger\Exception\HandlerFailedException $e) {
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

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['DELETE'])]
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

    #[Route('/find', methods: ['GET'])]
    public function find(Request $request): JsonResponse
    {
        $agreementLineId = $request->query->get('agreementLineId');
        $type = $request->query->get('type');

        if ($agreementLineId === null && $type === null) {
            return $this->json([]);
        }

        if ($agreementLineId !== null && (!is_numeric($agreementLineId) || (int) $agreementLineId <= 0)) {
            return $this->json(['error' => 'agreementLineId must be a positive integer'], Response::HTTP_BAD_REQUEST);
        }

        if ($type !== null && TaskTypeEnum::tryFrom($type) === null) {
            $validValues = implode(', ', array_column(TaskTypeEnum::cases(), 'value'));
            return $this->json(['error' => "type must be one of: $validValues"], Response::HTTP_BAD_REQUEST);
        }

        $criteria = [];

        if ($agreementLineId !== null) {
            $agreementLine = $this->agreementLineRepository->find((int) $agreementLineId);
            if ($agreementLine === null) {
                return $this->json(['error' => 'AgreementLine not found'], Response::HTTP_NOT_FOUND);
            }
            $criteria['agreementLine'] = $agreementLine;
        }

        if ($type !== null) {
            $criteria['type'] = $type;
        }

        $tasks = $this->taskRepository->findBy($criteria, ['dateStart' => 'ASC']);

        return $this->json(array_map(fn(Task $task) => TaskDTO::fromEntity($task), $tasks));
    }
}
