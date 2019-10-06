<?php

namespace App\Controller;

use App\Entity\AgreementLine;
use App\Entity\Production;
use App\Entity\StatusLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AgreementLineRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class AgreementLineController extends AbstractController
{
    /**
     * @Route("/agreement/line/{id}", name="agreement_line_details", methods={"GET"}, options={"expose"=true})
     * @param Request $request
     * @param AgreementLine $agreementLine
     * @return Response
     */
    public function details(Request $request, AgreementLine $agreementLine)
    {
        return $this->render('agreement_line/order_details.html.twig', [
            'title' => 'Panel zamówienia',
            'agreementLineId' => $request->attributes->getInt('id'),
            'statuses' => AgreementLine::getStatuses()
        ]);
    }

    /**
     * @isGranted("ROLE_PRODUCTION_VIEW")
     *
     * @Route("/agreement/fetch", name="agreements_fetch", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param AgreementLineRepository $repository
     * @return JsonResponse
     */
    public function fetch(Request $request, AgreementLineRepository $repository)
    {
        $search = $request->request->all();

        if ($this->isGranted('ROLE_CUSTOMER')) {
            $search['search']['ownedBy'] = $this->getUser();
        }

        $agreements = $repository->getFiltered($search);

        return $this->json(
            [
                'orders' => array_map(function ($record) {
                    return [
                        'header' => [
                            'id' => $record['Agreement']['id'],
                            'status' => $record['Agreement']['status'],
                            'orderNumber' => $record['Agreement']['orderNumber'],
                            'createDate' => $record['Agreement']['createDate']->format('Y-m-d'),
                        ],
                        'customer' => $record['Agreement']['Customer'],
                        'product' => $record['Product'],
                        'production' => [
                            'data' => array_map(function ($prod) {
                                return [
                                    'id' => $prod['id'],
                                    'status' => (int) $prod['status'],
                                    'departmentSlug' => $prod['departmentSlug'],
                                    'dateStart' => is_object($prod['dateStart']) ? ($prod['dateStart'])->format('Y-m-d') : null,
                                    'dateEnd' => is_object($prod['dateEnd']) ? ($prod['dateEnd']->format('Y-m-d')) : null,
                                    'description' => $prod['description'],
                                    'title' => $prod['title'],
                                    'statusLog' => array_map(function($status) {
                                        return [
                                            'createdAt' => is_object($status['createdAt']) ? ($status['createdAt'])->format('Y-m-d H:m:s') : null,
                                            'currentStatus' => (int) $status['currentStatus']
                                        ];
                                    }, $prod['statusLogs'])
                                ];
                            }, $record['productions'])
                        ],
                        'line' => [
                            'id' => $record['id'],
                            'factor' => $record['factor'],
                            'confirmedDate' => $record['confirmedDate']->format('Y-m-d'),
                            'status' => $record['status'],
                            'description' => $record['description']
                        ]    
                    ];
                }, $agreements->getArrayResult()),
                
                'departments' => \App\Entity\Department::names(),
            ]
        );
    }

    /**
     * @isGranted("ROLE_PRODUCTION")
     *
     * @Route("/agreement_line/update/{id}", name="agreement_line_update", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param AgreementLine $agreementLine
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(Request $request, AgreementLine $agreementLine, EntityManagerInterface $em): JsonResponse
    {
        // zapis produkcji
        $retStatus = [];

        // elementy produkcji przed zapisem
        $productionOld = array_map(function($i) { return $i->getId(); }, $em->getRepository(Production::class)->findBy(['agreementLine' => $agreementLine]));
        $productionIncoming = array_map(function($i) { return $i['id']; }, $request->request->get('productionData'));

        try {
            foreach ($request->request->get('productionData') as $prod) {

                if (!$prod['id']) {
                    $production = new Production();
                    $production
                        ->setCreatedAt(new \DateTime())
                        ->setAgreementLine($agreementLine)
//                    ->setDescription($prod['description'])
                        ->setDepartmentSlug($prod['departmentSlug'])//                    ->setTitle($prod['title'])
                    ;
                } else {
                    $production = $em->getRepository(Production::class)->find($prod['id']);
//                if (($idx = array_search($prod['id'], $productionOld) !== false)) {
//                    unset($productionOld[])
//                }
                }
                $oldStatus = $production->getStatus();
                $production
                    ->setStatus((int)$prod['status'])
                    ->setUpdatedAt(new \DateTime());

                if ($prod['title']) {
                    $production->setTitle($prod['title']);
                }

                if ($prod['description']) {
                    $production->setDescription($prod['description']);
                }

                if ($prod['dateStart']) {
                    $production->setDateStart(new \DateTime($prod['dateStart']));
                }

                if ($prod['dateEnd']) {
                    $production->setDateEnd(new \DateTime($prod['dateEnd']));
                }

                $em->persist($production);

                if ($oldStatus != (int)$prod['status']) {
                    $newStatus = new StatusLog();
                    $newStatus
                        ->setCurrentStatus((int)$prod['status'])
                        ->setProduction($production);
                    $em->persist($newStatus);

                    $retStatus[] = [
                        'currentStatus' => (int)$newStatus->getCurrentStatus(),
                        'createdAt' => $newStatus->getCreatedAt()->format('Y-m-d H:i:s'),
                        'productionId' => $production->getId()
                    ];
                }
            }

            // zapis agreement line
            $line = $request->request->get('agreementLineData');

            if ($line) {
                if ($line['confirmedDate']) {
                    $agreementLine->setConfirmedDate(new \DateTime($line['confirmedDate']));
                }
                $agreementLine->setDescription($line['description']);
                $agreementLine->setStatus((int)$line['status']);
                $em->persist($agreementLine);
            }

            // usunięcie
            foreach (array_diff($productionOld, $productionIncoming) as $toDelete) {
                $em->remove($em->getRepository(Production::class)->find($toDelete));
            }

            $em->flush();
        } catch (\Exception $e) {
            return $this->json([$e->getMessage()], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json(['newStatuses' => $retStatus]);
    }

    /**
     * @isGranted("ROLE_PRODUCTION")
     *
     * @Route("/agreement_line/archive/{id}", name="agreement_line_archive", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param AgreementLine $agreementLine
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function archive(Request $request, AgreementLine $agreementLine, EntityManagerInterface $em)
    {
        $agreementLine->setArchived(true);
        $em->flush();

        return $this->json();
    }

    /**
     * @isGranted("ROLE_PRODUCTION")
     *
     * @Route("/agreement_line/delete/{agreementLine}", name="agreement_line_delete", methods={"POST"}, options={"expose"=true})
     * @param AgreementLine $agreementLine
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function delete(AgreementLine $agreementLine, EntityManagerInterface $em)
    {
        $agreementLine->setDeleted(true);
        $em->flush();

        return new JsonResponse();
    }
}
