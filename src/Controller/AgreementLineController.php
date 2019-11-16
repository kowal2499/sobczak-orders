<?php

namespace App\Controller;

use App\Entity\AgreementLine;
use App\Entity\Production;
use App\Entity\StatusLog;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AgreementLineRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;


class AgreementLineController extends AbstractController
{
    /**
     * @Route("/agreement/line/{id}", name="agreement_line_details", methods={"GET"}, options={"expose"=true})
     * @param Request $request
     * @param AgreementLine $agreementLine
     * @return Response
     */
    public function details(Request $request, AgreementLine $agreementLine, TranslatorInterface $t)
    {
        $statuses = [];
        foreach (AgreementLine::getStatuses() as $key => $value) {
            $statuses[$key] = $t->trans($value, [], 'agreements');
        }

        return $this->render('agreement_line/order_details.html.twig', [
            'title' => $t->trans('Panel zamówienia', [], 'agreements'),
            'agreementLineId' => $request->attributes->getInt('id'),
            'statuses' => $statuses
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
    public function fetch(Request $request, AgreementLineRepository $repository, TranslatorInterface $t, PaginatorInterface $paginator)
    {
        $search = $request->request->all();

        if ($this->isGranted('ROLE_CUSTOMER')) {
            $search['search']['ownedBy'] = $this->getUser();
        }

        $agreements = $paginator->paginate(
            $repository->getFiltered($search),
            $search['search']['page'] ?? 1,
            20
        );

        $paginationMeta = ($agreements->getPaginationData());

        $result = [];

        foreach ($agreements as $agreement) {

            $productionData = [];
            foreach ($agreement->getProductions() as $prod) {
                /** @var Production $prod */

                $statusLog = [];
                foreach ($prod->getStatusLogs() as $log) {
                    $statusLog[] = [
                        'createdAt' => $log->getCreatedAt(),
                        'currentStatus' => $log->getCurrentStatus(),
                        'user' => $log->getUser() ? $log->getUser()->getUserFullName() : ''
                    ];
                }

                $productionData[] = [
                    'dateEnd' => $prod->getDateEnd(),
                    'dateStart' => $prod->getDateStart(),
                    'departmentSlug' => $prod->getDepartmentSlug(),
                    'description' => $prod->getDescription(),
                    'id' => (int)$prod->getId(),
                    'status' => (int)$prod->getStatus(),
                    'title' => $prod->getTitle(),
                    'statusLog' => $statusLog,
                ];
            }

            /** @var AgreementLine $agreement */
            $result['orders'][] = [
                'header' => [
                    'id' => $agreement->getAgreement()->getId(),
                    'status' => $agreement->getAgreement()->getStatus(),
                    'orderNumber' => $agreement->getAgreement()->getOrderNumber(),
                    'createDate' => $agreement->getAgreement()->getCreateDate()->format('Y-m-d'),
                ],
                'customer' => [
                    'apartment_number' => $agreement->getAgreement()->getCustomer()->getApartmentNumber(),
                    'city' => $agreement->getAgreement()->getCustomer()->getCity(),
                    'country' => $agreement->getAgreement()->getCustomer()->getCountry(),
                    'createDate' => $agreement->getAgreement()->getCustomer()->getCreateDate()->format('Y-m-d'),
                    'updateDate' => $agreement->getAgreement()->getCustomer()->getUpdateDate()->format('Y-m-d'),
                    'email' => $agreement->getAgreement()->getCustomer()->getEmail(),
                    'first_name' => $agreement->getAgreement()->getCustomer()->getFirstName(),
                    'id' => $agreement->getAgreement()->getCustomer()->getId(),
                    'last_name' => $agreement->getAgreement()->getCustomer()->getLastName(),
                    'name' => $agreement->getAgreement()->getCustomer()->getName(),
                    'phone' => $agreement->getAgreement()->getCustomer()->getPhone(),
                    'postal_colde' => $agreement->getAgreement()->getCustomer()->getPostalCode(),
                    'street' => $agreement->getAgreement()->getCustomer()->getStreet(),
                    'street_number' => $agreement->getAgreement()->getCustomer()->getStreetNumber(),
                ],
                'product' => [
                    'createDate' => $agreement->getProduct()->getCreateDate()->format('Y-m-d'),
                    'description' => $agreement->getProduct()->getDescription(),
                    'factor' => $agreement->getProduct()->getFactor(),
                    'id' => $agreement->getProduct()->getId(),
                    'name' => $agreement->getProduct()->getName()
                ],
                'production' => [
                    'data' => $productionData
                ],
                'line' => [
                    'id' => $agreement->getId(),
                    'factor' => $agreement->getFactor(),
                    'confirmedDate' => $agreement->getConfirmedDate()->format('Y-m-d'),
                    'status' => $agreement->getStatus(),
                    'description' => $agreement->getDescription()
                ]
            ];
        }

        $result['departments'] = array_map(function($dpt) use($t) { return ['name' => $t->trans($dpt['name'], [], 'agreements'), 'slug' => $dpt['slug']]; },
        \App\Entity\Department::names());

        return $this->json([
            'data' => $result,
            'meta' => [
                'current' => $paginationMeta['current'],
                'pages' => $paginationMeta['pageCount'],
                'totalCount' => $paginationMeta['totalCount'],
                'pageSize' => $paginationMeta['numItemsPerPage']
            ],
        ]);

//        return $this->json(
//            [
//                'orders' => array_map(function ($record) {
//                    var_dump($record);
//                    /** @var AgreementLine $record */
//                    return [
//                        'header' => [
//                            'id' => $record->getAgreement()->getId(),
//                            'status' => $record->getAgreement()->getStatus(),
//                            'orderNumber' => $record->getAgreement()->getOrderNumber(),
//                            'createDate' => $record->getAgreement()->getCreateDate()->format('Y-m-d'),
//                        ],
//                        'customer' => $record->getAgreement()->getCustomer(),
//                        'product' => $record->getProduct(),
//                        'production' => [
//                            'data' => array_map(function ($prod) {
//                                /** @var Production $prod */
//                                return [
//                                    'id' => $prod->getId(),
//                                    'status' => (int) $prod->getStatus(),
//                                    'departmentSlug' => $prod->getDepartmentSlug(),
//                                    'dateStart' => is_object($prod->getDateStart()) ? ($prod->getDateStart())->format('Y-m-d') : null,
//                                    'dateEnd' => is_object($prod->getDateEnd()) ? ($prod->getDateEnd()->format('Y-m-d')) : null,
//                                    'description' => $prod->getDescription(),
//                                    'title' => $prod->getTitle(),
//                                    'statusLog' => array_map(function($status) {
//                                        /** @var StatusLog $status */
//                                        return [
//                                            'createdAt' => is_object($status->getCreatedAt()) ? ($status->getCreatedAt())->format('Y-m-d H:m:s') : null,
//                                            'currentStatus' => (int) $status->getCurrentStatus(),
//                                            'user' => $status->getUser() ? sprintf('%s %s', $status->getUser()->getFirstName(), $status->getUser()->getLastName()) : ''
//                                        ];
//                                    }, $prod->getStatusLogs())
//                                ];
//                            }, $record->getProductions())
//                        ],
//                        'line' => [
//                            'id' => $record->getId(),
//                            'factor' => $record->getFactor(),
//                            'confirmedDate' => $record->getConfirmedDate()->format('Y-m-d'),
//                            'status' => $record->getStatus(),
//                            'description' => $record->getDescription()
//                        ]
//                    ];
//                }, (array)$agreements),
//
//                'departments' => array_map(function($dpt) use($t) { return ['name' => $t->trans($dpt['name'], [], 'agreements'), 'slug' => $dpt['slug']]; },
//                    \App\Entity\Department::names()
//                ),
//            ]
//        );
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
                        ->setProduction($production)
                        ->setUser($this->getUser());
                    $em->persist($newStatus);

                    $retStatus[] = [
                        'currentStatus' => (int)$newStatus->getCurrentStatus(),
                        'createdAt' => $newStatus->getCreatedAt()->format('Y-m-d H:i:s'),
                        'productionId' => $production->getId(),
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
     * @Route("/agreement_line/archive/{id}/{statusId}", name="agreement_line_archive", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param AgreementLine $agreementLine
     * @param $statusId
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function setStatus(Request $request, AgreementLine $agreementLine, $statusId, EntityManagerInterface $em)
    {
        $agreementLine->setStatus((int)$statusId);
        $em->flush();

        return $this->json([]);
    }

    /**
     * @isGranted("ROLE_ADMIN")
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
