<?php

namespace App\Controller;

use App\Entity\StatusLog;
use App\Repository\StatusLogRepository;
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

            // jeżeli produkcja jeszcze nie istnieje
            if (!($production)) {
                $production = new Production();

                $production
                    ->setAgreementLine($agreementLine)
                    ->setDepartmentSlug($plan['slug'])
                    ->setStatus((int) $plan['status'])
                    ->setTitle($plan['name'])
                    ->setCreatedAt(new \DateTime())
                ;

                // zmiana statusu na "w produkcji"
                $agreementLine->setStatus(AgreementLine::STATUS_MANUFACTURING);
                $em->persist($agreementLine);
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
                    ->setCreatedAt(new \DateTime())
                    ->setProduction($prod)
                    ->setUser($this->getUser());
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


}
