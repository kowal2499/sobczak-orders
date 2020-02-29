<?php

namespace App\Controller;

use App\Repository\AgreementLineRepository;
use App\Repository\ProductionRepository;
use App\Repository\UserRepository;
use App\Service\CockpitIndicatorsService;
use App\Service\WorkingScheduleService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;


class DashboardController extends BaseController
{
    /**
     * @Route("/", name="dashboard_show")
     */
    public function index(TranslatorInterface $t)
    {
        return $this->render('dashboard/show.html.twig', [
            'title' => $t->trans('Pulpit', [], 'dashboard')
        ]);
    }

    /**
     * @Route("/fetch_orders_count", name="api_fetch_orders_count", methods={"POST"}, options={"expose"=true})
     * @param AgreementLineRepository $repository
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function ApiFetchOrdersCount(AgreementLineRepository $repository)
    {
        return $this->json($repository->getSummary());
    }

    /**
     * @Route("/dashboard/factors/{time}", name="factors_summary", options={"expose"=true})
     * @param $time
     * @param CockpitIndicatorsService $cockpitIndicatorsService
     * @param TranslatorInterface $t
     * @return JsonResponse
     */
    public function ApiFetchFactorsSummary($time, CockpitIndicatorsService $cockpitIndicatorsService, TranslatorInterface $t)
    {
        try {
            $datetime =\DateTimeImmutable::createFromFormat('Y-m', $time)->modify('first day of this month')->setTime(0, 0, 0);

            $valid = \DateTimeImmutable::getLastErrors();
            if ($valid['warning_count'] > 0 || $valid['error_count'] > 0) {
                throw new \Exception($t->trans('Nieprawidłowy format daty', [], 'dashboard'));
            }

            $stats = $cockpitIndicatorsService->calculate($datetime);

        } catch (\Exception $e) {
            return $this->composeErrorResponse($e);
        }

        return $this->json($stats);
    }

    /**
     * @Route("/dashboard/production", name="production_summary", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param WorkingScheduleService $workingScheduleService
     * @param ProductionRepository $repository
     * @return JsonResponse
     */
    public function ApiFetchProductionSummary(Request $request, WorkingScheduleService $workingScheduleService, ProductionRepository $repository)
    {
        $argMonth = $request->request->getInt('month');
        $argYear = $request->request->getInt('year');
        $factorsPerDay = 1.5238;

        $summary = [
            'production' => [
                'ordersInProduction' => 0,
                'ordersFinished' => 0,
                'factorsInProduction' => 0,
                'factorsFinished' => 0,
            ],

            'firstFreeDay' => null,
        ];

        try {
            /**
             * Wyznaczanie obłożenia produkcji
             */

            /**
             * Musimy wyznaczyć:
             *
             * 1. Produkcja w toku
             *
             * Pobieramy produkcje których `created_at` jest mniejszy lub równy granicznej dacie
             * i `department_slug` wynosi 'dpt05' i `status` jest różny od 3. Czyli te, których pakowanie
             * nie zostało jeszcze zakończone.
             * Joinujemy `agreement_line`, pobieramy produkt i sumujemy współczynniki.
             * Dzięki temu wiemy jaka wartość współczynników jest jeszcze do wykonania.
             *
             * 2. Produkcja zakończona
             * Bierzemy produkcje zakończone, które zostały zakończone w zadanym okresie
             * Decycydująca jest data kiedy produkcja otrzyma status 3 przy 'dpt05'
             */

            $linesFinished = $repository->getCompletedAgreementLines($argMonth, $argYear);

            foreach ($linesFinished as $line) {
                $summary['production']['ordersFinished'] += 1;
                $summary['production']['factorsFinished'] += (float) $line->getAgreementLine()->getFactor();
            }

            $query = $repository->getNotCompletedAgreementLines($argMonth, $argYear);

            foreach ($repository
                         ->withConnectedCustomers($query)
                         ->getQuery()
                         ->getResult() as $line) {
                $summary['production']['ordersInProduction'] += 1;
            }

            // bez połączonych klientów
            foreach ($query
                         ->getQuery()
                         ->getResult() as $line) {
                $summary['production']['factorsInProduction'] += (float) $line->getAgreementLine()->getFactor();
            }

            /**
             * Dzień zakończenia bieżącej produkcji
             */
            $daysToFinish = ceil($summary['production']['factorsInProduction'] / $factorsPerDay);

            $endDate = new \DateTime();
            $cachedMonth = null;
            $index = 0;
            while ($index < $daysToFinish) {
                $endDate->modify('+1 day');
                if ($cachedMonth !== $endDate->format('m')) {
                    $workingScheduleService->initialize($endDate->format('Y-m') . '-01');
                }
                if ($workingScheduleService->isWorkingDay($endDate->format('Y-m-d'))) {
                    $index++;
                }
            }
            $summary['firstFreeDay'] = $endDate->format('Y-m-d');

        } catch (\Exception $e) {
            return $this->composeErrorResponse($e);
        }
        return $this->json($summary);
    }

}