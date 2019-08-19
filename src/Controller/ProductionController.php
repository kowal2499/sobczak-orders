<?php

namespace App\Controller;

use App\Entity\StatusLog;
use App\Repository\StatusLogRepository;
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

class ProductionController extends AbstractController
{
    /**
     * @Route("/production", name="production_show")
     */
    public function index()
    {
        return $this->render('production/production_show.html.twig', [
            'title' => 'Harmonogram produkcji'
        ]);
    }

    /**
     * @Route ("/production/save", name="production_save", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function save(Request $request, ProductionRepository $repository, EntityManagerInterface $em)
    {

        $plans = $request->request->get('plan');
        $lineId = $request->request->get('orderLineId');

        $response = [];
        $prodStack = [];

        $agreementLine = $em->getRepository(AgreementLine::class)->find($lineId);

        foreach ($plans as $plan) {
            $production = $repository->findOneBy([
                'agreementLine' => $agreementLine,
                'departmentSlug' => $plan['slug']]
            );

            if (!($production)) {
                $production = new Production();

                $production
                    ->setAgreementLine($agreementLine)
                    ->setDepartmentSlug($plan['slug'])
                    ->setStatus((int) $plan['status'])
                    ->setCreatedAt(new \DateTime())
                ;
            }

            $production
                ->setStatus((int) $plan['status'])
                ->setUpdatedAt(new \DateTime())
            ;

            if ($plan['dateFrom']) {
                $production->setDateStart(new \DateTime($plan['dateFrom']));
            }

            if ($plan['dateTo']) {
                $production->setDateEnd(new \DateTime($plan['dateTo']));
            }

            $em->persist($production);
            $prodStack[] = $production;
        }
        $em->flush();


        // dodaj statusy
        foreach ($prodStack as $idx => $prod) {

            $lastStatus = $em
                ->getRepository(StatusLog::class)
                ->findLast($prod);

            // zapis tylko gdy status się zmienił lub gdy nie ma powiązanego statusu
            if (!$lastStatus || ($lastStatus->getCurrentStatus() != $plans[$idx]['status'])) {
                $newStatus = new StatusLog();
                $newStatus
                    ->setCurrentStatus($plans[$idx]['status'])
                    ->setProduction($prod);
                $em->persist($newStatus);
            }
        }

        $em->flush();


        foreach ($prodStack as $prod) {

            $statuses = $em->getRepository(StatusLog::class)->findBy(['production' => $prod]);

            $response[] = [
                'id' => $prod->getId(),
                'status' => (int) $prod->getStatus(),
                'departmentSlug' => $prod->getDepartmentSlug(),
                'dateStart' => $prod->getDateStart() ? ($prod->getDateStart()->format('Y-m-d')) : null,
                'dateEnd' => $prod->getDateEnd() ? ($prod->getDateEnd()->format('Y-m-d')) : null,
                'statusLog' => array_map(function($status) {
                    return [
                        'currentStatus' => (int) $status->getCurrentStatus(),
                        'createdAt' => $status->getCreatedAt()->format('Y-m-d H:m:s')
                    ];
                }, $statuses)

            ];
        }


        return new JsonResponse([$response]);
    }

    /**
     * @Route("/production/update_status", name="production_status_update", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param ProductionRepository $repository
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function updateStatus(Request $request, ProductionRepository $repository, EntityManagerInterface $em)
    {

        $production = $repository->findOneBy(['id' => $request->request->getInt('productionId')]);
        $production->setStatus($request->request->getInt('newStatus'));

        $statusLog = new StatusLog();
        $statusLog
            ->setProduction($production)
            ->setCurrentStatus($request->request->getInt('newStatus'));
        $em->persist($statusLog);

        $em->flush();

        return new JsonResponse(['ok']);
    }

    /**
     * @Route("/production/delete/{agreementLine}", name="production_delete", methods={"POST"}, options={"expose"=true})
     * @param AgreementLine $agreementLine
     * @return JsonResponse
     */
    public function delete(AgreementLine $agreementLine, EntityManagerInterface $em)
    {
        foreach ($agreementLine->getProductions() as $production) {
            $em->remove($production);
        }
        $em->flush();
        return new JsonResponse();
    }

    /**
     * @Route("/production/summary", name="production_summary", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @return JsonResponse
     */
    public function summary(Request $request, ProductionRepository $repository)
    {

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

        $linesInProduction = $repository->getNotCompletedAgreementLines($request->request->getInt('month'), $request->request->getInt('year'));
        $linesFinished = $repository->getCompletedAgreementLines($request->request->getInt('month'), $request->request->getInt('year'));

        $summary = [
            'ordersInProduction' => 0,
            'ordersFinished' => 0,
            'factorsInProduction' => 0,
            'factorsFinished' => 0,
        ];

        foreach ($linesFinished as $line) {
            $summary['ordersFinished'] += 1;
            $summary['factorsFinished'] += (float) $line->getAgreementLine()->getFactor();
        }

        foreach ($linesInProduction as $line) {
            $summary['ordersInProduction'] += 1;
            $summary['factorsInProduction'] += (float) $line->getAgreementLine()->getFactor();
        }

        return new JsonResponse($summary);
    }
}
