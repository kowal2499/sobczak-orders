<?php

namespace App\Module\Production\Controller;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Production;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\Module\Agreement\Command\LogProductionDateChangedCommand;
use App\Module\Agreement\Event\AgreementLineWasUpdatedEvent;
use App\System\CommandBus;
use App\System\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class ProductionDatesController extends AbstractController
{
    #[Route(path: '/{id}/dates', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('production.panel')]
    public function updateDates(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        CommandBus $commandBus,
        EventBus $eventBus,
    ): JsonResponse {
        $production = $em->find(Production::class, $id);
        if ($production === null) {
            return $this->json(['error' => 'Production task not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$production->isGhost() && !$this->isEditableStatus($production->getStatus())) {
            return $this->json(
                ['error' => 'Only pending or in-progress production tasks can be rescheduled'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $payload = $request->toArray();
        $dateStart = $payload['dateStart'] ?? null;
        $dateEnd = $payload['dateEnd'] ?? null;

        $oldStart = $production->getDateStart()?->format('Y-m-d');
        $oldEnd = $production->getDateEnd()?->format('Y-m-d');

        if ($dateStart !== null) {
            $production->setDateStart(new \DateTime($dateStart));
        }
        if ($dateEnd !== null) {
            $production->setDateEnd(new \DateTime($dateEnd));
        }
        $production->setUpdatedAt(new \DateTime());

        $em->flush();

        $this->logDateChanges($production, $oldStart, $oldEnd, $commandBus);

        $agreementLine = $production->getAgreementLine();
        if ($agreementLine !== null) {
            $eventBus->dispatch(new AgreementLineWasUpdatedEvent($agreementLine->getId()));
        }

        return $this->json($production, Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['_linePanel'],
        ]);
    }

    private function isEditableStatus(?string $status): bool
    {
        return !in_array((string) $status, [
            (string) TaskTypes::TYPE_DEFAULT_STATUS_COMPLETED,
            (string) TaskTypes::TYPE_DEFAULT_STATUS_NOT_APPLICABLE,
        ], true);
    }

    private function logDateChanges(
        Production $production,
        ?string $oldStart,
        ?string $oldEnd,
        CommandBus $commandBus,
    ): void {
        $newStart = $production->getDateStart()?->format('Y-m-d');
        $newEnd = $production->getDateEnd()?->format('Y-m-d');

        if ($oldStart !== $newStart) {
            $commandBus->dispatch(new LogProductionDateChangedCommand(
                $production->getId(),
                AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED,
                $oldStart,
                $newStart,
            ));
        }

        if ($oldEnd !== $newEnd) {
            $commandBus->dispatch(new LogProductionDateChangedCommand(
                $production->getId(),
                AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_END_CHANGED,
                $oldEnd,
                $newEnd,
            ));
        }
    }
}
