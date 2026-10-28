<?php

namespace App\Module\MetricSnapshot\Controller;

use App\Controller\BaseController;
use App\Module\MetricSnapshot\Command\CaptureMetricSnapshotCommand;
use App\Module\MetricSnapshot\Query\GetMetricHistoryQuery;
use App\Module\MetricSnapshot\Repository\MetricSnapshotRepository;
use App\Module\MetricSnapshot\Service\MetricVisibility;
use App\Module\MetricSnapshot\ValueObject\SnapshotSource;
use App\System\CommandBus;
use App\System\QueryBus;
use App\Utilities\DateValidationTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/metric-snapshot')]
class MetricSnapshotController extends BaseController
{
    use DateValidationTrait;

    private const MAX_RANGE_DAYS = 400;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
        private readonly Security $security,
    ) {
    }

    #[Route('/history', methods: ['GET'])]
    #[IsGranted('metric-snapshot.view')]
    public function history(Request $request, MetricVisibility $visibility): Response
    {
        $result = $this->validateDateRange($request->query->get('from'), $request->query->get('to'));
        if ($result instanceof Response) {
            return $result;
        }
        ['start' => $from, 'end' => $to] = $result;

        if ($from->diff($to)->days > self::MAX_RANGE_DAYS) {
            return $this->json(['error' => 'Zakres jest zbyt długi'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->queryBus->query(new GetMetricHistoryQuery(
            $from,
            $to,
            $visibility->metrics(),
            $visibility->customerIds(),
            $visibility->departments(),
            $visibility->attentionLists(),
        )));
    }

    #[Route('/capture', methods: ['POST'])]
    #[IsGranted('metric-snapshot.manage')]
    public function capture(MetricSnapshotRepository $repository): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new CaptureMetricSnapshotCommand(
                date: (new \DateTimeImmutable('today'))->format('Y-m-d'),
                source: SnapshotSource::MANUAL,
                userId: $this->security->getUser()?->getId(),
            ));
        } catch (HandlerFailedException $e) {
            $nested = $e->getNestedExceptions()[0] ?? null;
            if ($nested instanceof \InvalidArgumentException) {
                return $this->json(['error' => $nested->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            throw $e;
        }

        return $this->json([
            'success' => true,
            'computedAt' => $repository->findLastComputedAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }
}
