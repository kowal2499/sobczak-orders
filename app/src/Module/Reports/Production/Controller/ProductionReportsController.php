<?php

namespace App\Module\Reports\Production\Controller;

use App\Controller\BaseController;
use App\Module\Reports\Production\Provider\DashboardMetricProvider;
use App\Utilities\DateValidationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductionReportsController extends BaseController
{
    use DateValidationTrait;

    #[Route(path: '/agreement-line-production-summary', methods: ['GET'])]
    public function agreementLinesProductionSummary(
        Request $request,
        DashboardMetricProvider $metrics,
    ): Response {

        $result = $this->validateDateRange(
            $request->query->get('start'),
            $request->query->get('end')
        );
        if ($result instanceof Response) {
            return $result;
        }
        ['start' => $start, 'end' => $end] = $result;

        return $this->json([
            'orders_pending' => $metrics->getMetric('orders_pending', $start, $end),
            'orders_finished' => $metrics->getMetric('orders_finished', $start, $end),
        ]);
    }

    #[Route(path: '/production-finished-details', methods: ['GET'])]
    public function productionFinishedDetails(
        Request $request,
        DashboardMetricProvider $metrics
    ): Response {
        $result = $this->validateDateRange(
            $request->query->get('start'),
            $request->query->get('end')
        );
        if ($result instanceof Response) {
            return $result;
        }
        ['start' => $start, 'end' => $end] = $result;

        return $this->json($metrics->getMetric('orders_finished_details', $start, $end));
    }

    #[Route(path: '/production-pending-details', methods: ['GET'])]
    public function productionPendingDetails(
        Request $request,
        DashboardMetricProvider $metrics
    ): Response {
        $result = $this->validateDateRange(
            $request->query->get('start'),
            $request->query->get('end'),
            false
        );
        if ($result instanceof Response) {
            return $result;
        }
        ['start' => $start, 'end' => $end] = $result;

        return $this->json($metrics->getMetric('orders_pending_details', $start, $end));
    }

    #[Route(path: '/production-tasks-completion-summary', methods: ['GET'])]
    public function productionTasksCompletionSummary(
        Request $request,
        DashboardMetricProvider $metrics
    ): Response {
        $result = $this->validateDateRange(
            $request->query->get('start'),
            $request->query->get('end')
        );
        if ($result instanceof Response) {
            return $result;
        }
        ['start' => $start, 'end' => $end] = $result;

        return $this->json($metrics->getMetric('departments_bonus', $start, $end));
    }

    #[Route(path: '/production-capacity', methods: ['GET'])]
    public function productionCapacity(
        Request $request,
        DashboardMetricProvider $metrics
    ): Response {
        $result = $this->validateDateRange(
            $request->query->get('start'),
            $request->query->get('end')
        );
        if ($result instanceof Response) {
            return $result;
        }
        ['start' => $start, 'end' => $end] = $result;

        $includeGhost = $request->query->getBoolean('includeGhost');

        return $this->json($metrics->getMetric('capacity', $start, $end, $includeGhost));
    }
}
