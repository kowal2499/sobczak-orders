<?php

namespace App\Module\Production\Controller;

use App\Entity\Production;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\Module\Agreement\Command\LogProductionDateChangedCommand;
use App\System\CommandBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class GhostProductionController extends AbstractController
{
    #[Route(path: '/ghost/{id}/dates', methods: ['PUT'])]
    public function updateDates(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        CommandBus $commandBus,
    ): JsonResponse {
        $production = $em->find(Production::class, $id);
        if ($production === null) {
            return $this->json(['error' => 'Production task not found'], Response::HTTP_NOT_FOUND);
        }
        if (!$production->isGhost()) {
            return $this->json(
                ['error' => 'Endpoint allowed only for ghost production tasks'],
                Response::HTTP_BAD_REQUEST
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

        return $this->json($production, Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['_linePanel'],
        ]);
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
