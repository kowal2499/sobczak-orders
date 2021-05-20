<?php

namespace App\Controller;

use App\DTO\Production\ProductionTaskDTO;
use App\Entity\Department;
use App\Entity\StatusLog;
use App\Repository\StatusLogRepository;
use App\Service\Production\DefaultTaskCreateService;
use App\Service\Production\ProductionTaskDatesResolver;
use App\Service\WorkingScheduleService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProductionRepository;
use App\Repository\AgreementLineRepository;
use App\Entity\Production;
use App\Entity\AgreementLine;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductionController extends BaseController
{
    /**
     * @isGranted("ROLE_PRODUCTION_VIEW")
     * @Route("/production", name="production_show")
     */
    public function index(TranslatorInterface $t)
    {
        return $this->render('production/production_show.html.twig', [
            'title' => $t->trans('Harmonogram produkcji', [], 'production'),
            'statuses' => AgreementLine::getStatuses(),
            'departments' => array_map(function($dpt) use($t) { return ['name' => $t->trans($dpt['name'], [], 'agreements'), 'slug' => $dpt['slug']]; },
                \App\Entity\Department::names())
        ]);
    }

    /**
     * @isGranted({"ROLE_ADMIN", "ROLE_PRODUCTION"})
     * @Route ("/production/start/{agreementLine}", methods={"POST"})
     * @param AgreementLine $agreementLine
     * @param ProductionTaskDatesResolver $datesResolver
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function startProduction(
        AgreementLine $agreementLine,
        ProductionTaskDatesResolver $datesResolver,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $prodStack = [];

        foreach (Department::names() as $task) {
            $production = new Production();
            $resolvedDateFrom = $datesResolver->resolveDateFrom();
            $production
                ->setAgreementLine($agreementLine)
                ->setTitle($task['name'])
                ->setDepartmentSlug($task['slug'])
                ->setStatus(0)
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime())
                ->setDateStart($datesResolver->resolveDateFrom())
                ->setDateEnd(
                    $datesResolver->resolveDateTo(
                        $task['slug'], $resolvedDateFrom, $agreementLine->getConfirmedDate()
                    ));

            $em->persist($production);

            $user = $this->getUser();
            $newStatus = new StatusLog();
            $newStatus
                ->setCurrentStatus($production->getStatus())
                ->setCreatedAt(new \DateTime())
                ->setProduction($production)
                ->setUser($user);
            $em->persist($newStatus);

            $prodStack[] = $production;
        }

        // update agreementLine status
        // todo: make a service for this
        $agreementLine->setStatus(AgreementLine::STATUS_MANUFACTURING);
        $em->persist($agreementLine);
        $em->flush();

        return $this->json($prodStack, Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['_linePanel']
        ]);
    }

    /**
     * @isGranted("ROLE_PRODUCTION")
     * @Route("/production/update_status", name="production_status_update", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param ProductionRepository $repository
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Exception
     */
    public function updateStatus(Request $request, ProductionRepository $repository, EntityManagerInterface $em)
    {

        $production = $repository->findOneBy(['id' => $request->request->getInt('productionId')]);
        $production->setStatus($request->request->getInt('newStatus'));

        $statusLog = new StatusLog();
        $statusLog
            ->setProduction($production)
            ->setCurrentStatus($request->request->getInt('newStatus'))
            ->setCreatedAt(new \DateTime())
            ->setUser($this->getUser())
        ;

        $em->persist($statusLog);

        $em->flush();

        return $this->json([]);
    }

    /**
     * @isGranted("ROLE_PRODUCTION")
     * @Route("/production/delete/{agreementLine}", name="production_delete", methods={"POST"}, options={"expose"=true})
     * @param AgreementLine $agreementLine
     * @return JsonResponse
     */
    public function delete(AgreementLine $agreementLine, EntityManagerInterface $em)
    {
        // zaktualizuj status
        $agreementLine->setStatus(AgreementLine::STATUS_WAITING);

        foreach ($agreementLine->getProductions() as $production) {
            $em->remove($production);
        }
        $em->flush();
        return $this->json([]);
    }

    /**
     * @Route("/production/summary", name="production_summary", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param WorkingScheduleService $workingScheduleService
     * @param ProductionRepository $repository
     * @return JsonResponse
     */
    public function summary(Request $request, WorkingScheduleService $workingScheduleService, ProductionRepository $repository)
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

            'workingDays' => null,
            'factorLimit' => null,
            'fistFreeDay' => null,
        ];

        try {
            /**
             * Wyznaczanie ilości dni roboczych
             */
            $workingScheduleService->initialize("${argYear}-${argMonth}-01");
            if (false === $workingScheduleService->hasHolidaysInitialized()) {
                $workingScheduleService->initializeHolidays();
            }
            $summary['workingDays'] = $workingScheduleService->getWorkingDaysCount();

            /**
             * Miesięczna norma produkcji to 32 współczynniki. W miesiący jest średnio 21 dni roboczoch,
             * co daje 1,5238 współczynnika na dzień.
             */

            $summary['factorLimit'] = floor($factorsPerDay * $summary['workingDays']);

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

            $linesFinished = $repository->getCompletedAgreementLines($request->request->getInt('month'), $request->request->getInt('year'));

            foreach ($linesFinished as $line) {
                $summary['production']['ordersFinished'] += 1;
                $summary['production']['factorsFinished'] += (float) $line->getAgreementLine()->getFactor();
            }

            $query = $repository->getNotCompletedAgreementLines($request->request->getInt('month'), $request->request->getInt('year'));

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
                    if (false === $workingScheduleService->hasHolidaysInitialized()) {
                        $workingScheduleService->initializeHolidays();
                    }
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
