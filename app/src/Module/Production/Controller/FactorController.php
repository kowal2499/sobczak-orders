<?php

namespace App\Module\Production\Controller;

use App\Controller\BaseController;
use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Module\Agreement\Event\AgreementLineWasUpdatedEvent;
use App\Module\Agreement\ReadModel\ProductionRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Production\Command\CreateFactorCommand;
use App\Module\Production\DTO\FactorRatioDTO;
use App\Module\Production\Entity\Factor;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Repository\FactorRepository;
use App\Module\Production\Service\FactorWriteService;
use App\Repository\AgreementLineRepository;
use App\System\CommandBus;
use App\System\EventBus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/factor', name: 'production_factor')]
class FactorController extends BaseController
{
    #[Route(path: '/{agreementLine}/completed-tasks-bonus', methods: ['POST'])]
    #[IsGranted('production.factor_adjustment')]
    public function storeCompletedTasksBonus(
        Request $request,
        AgreementLine $agreementLine,
        CommandBus $commandBus,
        EventBus $eventBus,
        AgreementLineRepository $agreementLineRepository,
        AgreementLineRMRepository $agreementLineRMRepository,
    ): JsonResponse {
        // front wysyła JSON — $request->request jest wtedy puste
        $payload = $request->request->count() > 0
            ? $request->request->all()
            : (array) json_decode((string) $request->getContent(), true);

        $departmentSlug = (string) ($payload['departmentSlug'] ?? '');
        $value = (float) ($payload['value'] ?? 0);
        $comment = trim((string) ($payload['comment'] ?? ''));

        if (!in_array($departmentSlug, TaskTypes::getDefaultSlugs(), true)) {
            return $this->json(['message' => 'Nieprawidłowy dział produkcji'], Response::HTTP_BAD_REQUEST);
        }
        if (0.0 === $value) {
            return $this->json(['message' => 'Wartość korekty nie może być zerowa'], Response::HTTP_BAD_REQUEST);
        }
        if ('' === $comment) {
            return $this->json(['message' => 'Komentarz do korekty jest wymagany'], Response::HTTP_BAD_REQUEST);
        }

        $commandBus->dispatch(new CreateFactorCommand(
            $agreementLine->getId(),
            new FactorRatioDTO(
                FactorSource::FACTOR_ADJUSTMENT_BONUS_COMPLETED_TASKS,
                $value,
                null,
                $departmentSlug,
                $comment,
            ),
        ));

        // kolekcja współczynników linii jest już załadowana — bez odświeżenia read model nie zobaczy nowego wpisu
        $agreementLineRepository->refresh($agreementLine);
        $eventBus->dispatch(new AgreementLineWasUpdatedEvent($agreementLine->getId()));

        $productions = $agreementLineRMRepository->find($agreementLine->getId())?->getProductions() ?? [];
        $production = current(array_filter(
            $productions,
            fn (ProductionRM $p) => $p->getDepartmentSlug() === $departmentSlug
        )) ?: null;

        return $this->json(
            $production?->getFactorBonusCompletedTasks()?->toArray() ?? ['factor' => null, 'factorsStack' => []],
            Response::HTTP_CREATED
        );
    }

    #[Route(path: '/{agreementLine}', methods: ['POST'])]
    #[IsGranted('production.factor_adjustment')]
    public function storeFromForm(
        Request $request,
        AgreementLine $agreementLine,
        FactorWriteService $factorWriteService,
        EventBus $eventBus,
    ): JsonResponse {
        $factors = (array) $request->request->get('factors', []);

        $factorWriteService->store(
            $agreementLine->getId(),
            array_map(fn ($data) => FactorRatioDTO::fromArray($data), $factors),
        );

        $eventBus->dispatch(new AgreementLineWasUpdatedEvent($agreementLine->getId()));

        return $this->json([], Response::HTTP_OK);
    }

    #[Route(path: '/{agreementLine}', methods: ['GET'])]
    public function readAsForm(AgreementLine $agreementLine, FactorRepository $factorRepository): JsonResponse
    {
        return $this->json(array_map(fn (Factor $factor) => [
            'id' => $factor->getId(),
            'departmentSlug' => $factor->getDepartmentSlug(),
            'value' => $factor->getFactorValue(),
            'source' => $factor->getSource()->value,
            'description' => $factor->getDescription(),
        ], $factorRepository->findBy(['agreementLine' => $agreementLine])), Response::HTTP_OK);
    }
}
