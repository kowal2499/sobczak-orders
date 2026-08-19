<?php

namespace App\Module\BonusSettlement\Controller;

use App\Controller\BaseController;
use App\Module\BonusSettlement\Command\CreateBonusPeriodCommand;
use App\Module\BonusSettlement\Command\RecalculateBonusPeriodCommand;
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

#[Route('/bonus-settlement')]
class BonusSettlementController extends BaseController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
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

    #[Route('/periods/{id}/recalculate', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('bonus-settlement.manage')]
    public function recalculate(int $id, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        $tolerance = $body['toleranceDays'] ?? null;

        return $this->dispatchPeriodAction(new RecalculateBonusPeriodCommand(
            periodId: $id,
            toleranceDays: is_numeric($tolerance) ? (int) $tolerance : null,
        ));
    }

    #[Route('/periods/{id}/reset-adjustments', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('bonus-settlement.manage')]
    public function resetAdjustments(int $id): JsonResponse
    {
        return $this->dispatchPeriodAction(new ResetBonusPeriodAdjustmentsCommand($id));
    }

    private function dispatchPeriodAction(object $command): JsonResponse
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
