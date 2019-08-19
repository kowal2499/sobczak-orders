<?php

namespace App\Controller;

use App\Entity\AgreementLine;
use App\Entity\Production;
use App\Entity\StatusLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AgreementLineRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class AgreementLineController extends AbstractController
{
    /**
     * @Route("/agreement/line/{id}", name="agreement_line_details", methods={"GET"}, options={"expose"=true})
     * @param Request $request
     * @param AgreementLine $agreementLine
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function details(Request $request, AgreementLine $agreementLine)
    {
        return $this->render('agreement_line/order_details.html.twig', [
            'title' => 'Panel zamówienia',
            'agreementLineId' => $request->attributes->getInt('id')
        ]);
    }

    /**
     * @Route("/agreement/fetch", name="agreements_fetch", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @return JsonResponse
     */
    public function fetch(Request $request, AgreementLineRepository $repository)
    {
        $agreements = $repository->getFiltered($request->request->all());

        return new JsonResponse(
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
                            'description' => $record['description']
                        ]    
                    ];
                }, $agreements->getArrayResult()),
                
                'departments' => \App\Entity\Department::names()
            ]    
        );


    }

    /**
     * @Route("/agreement_line/update/{id}", name="agreement_line_update", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param AgreementLine $agreementLine
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(Request $request, AgreementLine $agreementLine, EntityManagerInterface $em)
    {
        // zapis produkcji
        $retStatus = [];

        foreach ($request->request->get('productionData') as $prod) {

            $production = $em->getRepository(Production::class)->find($prod['id']);
            $oldStatus = $production->getStatus();
            $production
                ->setStatus((int)$prod['status'])
                ->setUpdatedAt(new \DateTime())
            ;

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
                    'currentStatus' => (int) $newStatus->getCurrentStatus(),
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
            $em->persist($agreementLine);
        }

        $em->flush();

        return new JsonResponse(
            [ 'newStatuses' => $retStatus ]
        );
    }

    /**
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

        return new JsonResponse();
    }

    /**
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
