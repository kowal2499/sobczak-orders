<?php

namespace App\Controller;

use App\Entity\Definitions\TaskTypes;
use App\Entity\Department;
use App\Entity\StatusLog;
use App\Exceptions\Production\ProductionAlreadyExistsException;
use App\Message\AgreementLine\UpdateProductionCompletionDate;
use App\Message\AgreementLine\UpdateProductionStartDate;
use App\Message\Task\UpdateStatusCommand;
use App\Repository\StatusLogRepository;
use App\Service\Production\ProductionTaskDatesResolver;
use App\Service\WorkingScheduleService;
use Symfony\Component\Messenger\MessageBusInterface;
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
     */
    #[Route(path: '/production', name: 'production_show')]
    public function index(TranslatorInterface $t): Response
    {
        return $this->render('production/production_show.html.twig', [
            'title' => $t->trans('Harmonogram produkcji', [], 'production'),
            'statuses' => AgreementLine::getStatuses(),
            'departments' => array_map(function($dpt) use($t) { return ['name' => $t->trans($dpt['name'], [], 'agreements'), 'slug' => $dpt['slug']]; },
                \App\Entity\Department::names())
        ]);
    }

    /**
     * @IsGranted("ROLE_PRODUCTION")
     * @param AgreementLine $agreementLine
     * @param ProductionTaskDatesResolver $datesResolver
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws ProductionAlreadyExistsException
     */
    #[Route(path: '/production/start/{agreementLine}', methods: ['POST'])]
    public function startProduction(
        AgreementLine $agreementLine,
        ProductionTaskDatesResolver $datesResolver,
        EntityManagerInterface $em,
        MessageBusInterface $messageBus
    ): JsonResponse
    {
        $repository = $em->getRepository(Production::class);
        if ($repository->findBy([
            'agreementLine' => $agreementLine,
            'departmentSlug' => Department::getSlugs()
        ])) {
            throw new ProductionAlreadyExistsException();
        }

        $response = [];
        foreach (Department::names() as $task) {
            $production = new Production();
            $production
                ->setAgreementLine($agreementLine)
                ->setTitle($task['name'])
                ->setDepartmentSlug($task['slug'])
                ->setCreatedAt(new \DateTime())
                ->setUpdatedAt(new \DateTime());

            $production->setDateStart(
                $datesResolver->resolveDateFrom($production, $agreementLine->getConfirmedDate())
            );
            $production->setDateEnd(
                $datesResolver->resolveDateTo($production, $agreementLine->getConfirmedDate())
            );

            $em->persist($production);
            $response[] = $production;
        }

        // update agreementLine status
        // todo: make a service for this
        $agreementLine->setStatus(AgreementLine::STATUS_MANUFACTURING);
        $em->persist($agreementLine);
        $em->flush();

        // set statuses
        array_map(function (Production $production) use ($messageBus) {
            $messageBus->dispatch(new UpdateStatusCommand(
                $production->getId(),
                TaskTypes::TYPE_DEFAULT_STATUS_AWAITS
            ));
        }, $response);

        $messageBus->dispatch(new UpdateProductionStartDate(
            $agreementLine->getId()
        ));

        return $this->json($response, Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['_linePanel']
        ]);
    }

    /**
     * @isGranted("ROLE_PRODUCTION")
     * @param Request $request
     * @param MessageBusInterface $messageBus
     * @param ProductionRepository $taskRepository
     * @return JsonResponse
     */
    #[Route(path: '/production/update_status', name: 'production_status_update', methods: ['POST'], options: ['expose' => true])]
    public function updateStatus(
        Request $request,
        MessageBusInterface $messageBus,
        ProductionRepository $taskRepository
    ): JsonResponse
    {
        $messageBus->dispatch(new UpdateStatusCommand(
            $request->request->getInt('productionId'),
            $request->request->getInt('newStatus')
        ));
        return $this->json($taskRepository->find($request->request->getInt('productionId')), Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['_linePanel']
        ]);
    }

    /**
     * @isGranted("ROLE_PRODUCTION")
     * @param AgreementLine $agreementLine
     * @return JsonResponse
     */
    #[Route(path: '/production/delete/{agreementLine}', name: 'production_delete', options: ['expose' => true], methods: ['POST'])]
    public function delete(
        AgreementLine $agreementLine,
        EntityManagerInterface $em,
        MessageBusInterface $messageBus
    ): JsonResponse
    {
        // todo:
        // most probably not used any more

        // zaktualizuj status
        $agreementLine->setStatus(AgreementLine::STATUS_WAITING);

        foreach ($agreementLine->getProductions() as $production) {
            $em->remove($production);
        }
        $em->flush();

        $messageBus->dispatch(new UpdateProductionStartDate($agreementLine->getId()));
        $messageBus->dispatch(new UpdateProductionCompletionDate($agreementLine->getId()));

        return $this->json([]);
    }

    /**
     * @param Request $request
     * @param WorkingScheduleService $workingScheduleService
     * @param ProductionRepository $repository
     * @return JsonResponse
     */
    #[Route(path: '/production/summary', name: 'production_summary', options: ['expose' => true], methods: ['POST'])]
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
             * Miesięczna norma produkcji to 32 współczynniki. W miesiącu jest średnio 21 dni roboczych,
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
             * Decydująca jest data kiedy produkcja otrzyma status 3 przy 'dpt05'
             */

            $linesFinished = $repository->getCompletedAgreementLines($request->request->getInt('month'), $request->request->getInt('year'));

            foreach ($linesFinished as $line) {
                $summary['production']['ordersFinished'] += 1;
                $summary['production']['factorsFinished'] += (float) $line->getAgreementLine()->getFactor();
                $summary['production']['finishedIds'][] = $line->getAgreementLine()->getId();
            }

            $query = $repository->getNotCompletedAgreementLines($request->request->getInt('month'), $request->request->getInt('year'));

            foreach ($repository
                         ->withConnectedCustomers($query)
                         ->getQuery()
                         ->getResult() as $line) {
                $summary['production']['ordersInProduction'] += 1;
                $summary['production']['pendingIds'][] = $line->getAgreementLine()->getId();
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
