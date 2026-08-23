<?php

namespace App\Module\BonusSettlement\Controller;

use App\Controller\BaseController;
use App\Module\ActivityLog\DTO\FieldFilter;
use App\Module\ActivityLog\DTO\PaginatedLogFilter;
use App\Module\ActivityLog\Query\GetPaginatedLogsQuery;
use App\Module\ActivityLog\Query\Helper\LogResponseMapper;
use App\Module\ActivityLog\ReadModel\PaginatedLogs;
use App\Module\BonusSettlement\Command\AdjustBonusEntryCommand;
use App\Module\BonusSettlement\Command\CloseBonusPeriodCommand;
use App\Module\BonusSettlement\Command\CreateBonusPeriodCommand;
use App\Module\BonusSettlement\Command\RecalculateBonusPeriodCommand;
use App\Module\BonusSettlement\Command\ReopenBonusPeriodCommand;
use App\Module\BonusSettlement\Command\ResetBonusPeriodAdjustmentsCommand;
use App\Module\BonusSettlement\Query\GetBonusPeriodQuery;
use App\Module\BonusSettlement\Query\GetBonusPeriodsQuery;
use App\System\CommandBus;
use App\System\QueryBus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/bonus-settlement')]
class BonusSettlementController extends BaseController
{
    private const MAX_LOGS_PER_PAGE = 100;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly Security $security,
        private readonly LogResponseMapper $logMapper,
    ) {
    }

    #[Route('/periods', methods: ['GET'])]
    #[IsGranted('bonus-settlement.view')]
    public function list(): JsonResponse
    {
        return $this->json(['data' => $this->queryBus->query(new GetBonusPeriodsQuery())]);
    }

    #[Route('/periods', methods: ['POST'])]
    #[IsGranted('bonus-settlement.manage')]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);

        if (!isset($body['year'], $body['month']) || !is_numeric($body['year']) || !is_numeric($body['month'])) {
            return $this->json(
                ['error' => 'year i month są wymagane i muszą być liczbami'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $this->commandBus->dispatch(new CreateBonusPeriodCommand(
                year: (int) $body['year'],
                month: (int) $body['month'],
            ));
        } catch (ValidationFailedException $e) {
            // Walidacja komendy siedzi w middleware szyny, więc leci przed handlerem i nie jest owinięta.
            return $this->violationResponse($e);
        } catch (HandlerFailedException $e) {
            return $this->errorFromHandler($e);
        }

        return $this->json(['success' => true], Response::HTTP_CREATED);
    }

    #[Route('/periods/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('bonus-settlement.view')]
    public function detail(int $id): JsonResponse
    {
        $period = $this->queryBus->query(new GetBonusPeriodQuery($id));

        if (null === $period) {
            return $this->json(['error' => 'Okres nie istnieje'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['data' => $period]);
    }

    /**
     * Dziennik okresu. Bramkowany grantem modułu, nie `activity-log.read` - pokazuje wyłącznie
     * wpisy `bonus.*` tego okresu, czyli to samo, co i tak widać w tabeli powyżej.
     */
    #[Route('/periods/{id}/logs', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('bonus-settlement.view')]
    public function logs(int $id, Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $pageSize = min(self::MAX_LOGS_PER_PAGE, max(1, (int) $request->query->get('pageSize', 20)));

        /** @var PaginatedLogs $result */
        $result = $this->queryBus->query(new GetPaginatedLogsQuery(
            null,
            new PaginatedLogFilter(
                page: $page,
                pageSize: $pageSize,
                fields: [new FieldFilter(name: 'periodId', value: (string) $id)],
                filterBy: null,
                typePrefix: 'bonus.',
            ),
        ));

        return $this->json(
            $this->logMapper->toPage($result->items, $result->total, $result->page, $result->pageSize)
        );
    }

    #[Route('/periods/{id}/recalculate', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('bonus-settlement.manage')]
    public function recalculate(int $id, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        $tolerance = $body['toleranceDays'] ?? null;

        return $this->dispatchCommand(new RecalculateBonusPeriodCommand(
            periodId: $id,
            toleranceDays: is_numeric($tolerance) ? (int) $tolerance : null,
        ));
    }

    #[Route('/periods/{id}/reset-adjustments', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('bonus-settlement.manage')]
    public function resetAdjustments(int $id): JsonResponse
    {
        return $this->dispatchCommand(new ResetBonusPeriodAdjustmentsCommand($id));
    }

    #[Route('/periods/{id}/close', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('bonus-settlement.manage')]
    public function close(int $id): JsonResponse
    {
        return $this->dispatchCommand(new CloseBonusPeriodCommand(
            periodId: $id,
            closedByUserId: $this->security->getUser()?->getId(),
        ));
    }

    #[Route('/periods/{id}/reopen', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('bonus-settlement.manage')]
    public function reopen(int $id): JsonResponse
    {
        return $this->dispatchCommand(new ReopenBonusPeriodCommand($id));
    }

    #[Route('/entries/{id}', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('bonus-settlement.manage')]
    public function adjustEntry(int $id, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        if (!is_array($body)) {
            return $this->json(['error' => 'Oczekiwano obiektu JSON'], Response::HTTP_BAD_REQUEST);
        }

        $value = $body['factorsAdjusted'] ?? null;
        if (null !== $value && !is_numeric($value)) {
            return $this->json(
                ['error' => 'factorsAdjusted musi być liczbą albo null'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $note = $body['note'] ?? null;

        return $this->dispatchCommand(new AdjustBonusEntryCommand(
            entryId: $id,
            factorsAdjusted: null === $value ? null : (float) $value,
            note: '' === $note ? null : $note,
        ));
    }

    private function dispatchCommand(object $command): JsonResponse
    {
        try {
            $this->commandBus->dispatch($command);
        } catch (ValidationFailedException $e) {
            return $this->violationResponse($e);
        } catch (HandlerFailedException $e) {
            return $this->errorFromHandler($e);
        }

        return $this->json(['success' => true]);
    }

    /**
     * Messenger owija wyjątki handlera. Walidacja komendy i konflikt stanu to błędy żądania,
     * nie awarie serwera, więc rozpakowujemy je na 422.
     */
    private function errorFromHandler(HandlerFailedException $exception): JsonResponse
    {
        $nested = $exception->getNestedExceptions()[0] ?? null;

        if ($nested instanceof ValidationFailedException) {
            return $this->violationResponse($nested);
        }

        if ($nested instanceof \InvalidArgumentException) {
            return $this->json(['error' => $nested->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        throw $exception;
    }

    private function violationResponse(ValidationFailedException $exception): JsonResponse
    {
        $violation = $exception->getViolations()->get(0);

        return $this->json(
            ['error' => $violation ? (string) $violation->getMessage() : 'Nieprawidłowe dane'],
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
