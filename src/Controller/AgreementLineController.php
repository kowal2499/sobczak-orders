<?php

namespace App\Controller;

use App\Entity\AgreementLine;
use App\Entity\Production;
use App\Entity\StatusLog;
use App\Form\AgreementLineType;
use App\Repository\AgreementRepository;
use App\Service\DateTimeHelper;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sluggable\Util\Urlizer;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AgreementLineRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Translation\TranslatorInterface;


class AgreementLineController extends BaseController
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
     * @param PaginatorInterface $paginator
     * @return JsonResponse
     */
    public function fetch(Request $request, AgreementLineRepository $repository, PaginatorInterface $paginator)
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

        return $this->json([
            'data' => $agreements,
            'meta' => [
                'current' => $paginationMeta['current'],
                'pages' => $paginationMeta['pageCount'],
                'totalCount' => $paginationMeta['totalCount'],
                'pageSize' => $paginationMeta['numItemsPerPage']
            ],
        ], Response::HTTP_OK, [], [
            AbstractNormalizer::GROUPS => ['_main', '_linePanel'],
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
        ]);
    }

    /**
     * @isGranted("ROLE_PRODUCTION")
     *
     * @Route("/agreement_line/update/{id}", name="agreement_line_update", methods={"PUT"}, options={"expose"=true})
     * @param Request $request
     * @param AgreementLine $agreementLine
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Exception
     */
    public function update(Request $request, AgreementLine $agreementLine, EntityManagerInterface $em): JsonResponse
    {
        $form = $this->createForm(AgreementLineType::class, $agreementLine);

        try {
            $this->processForm($request, $form);
            $agreementLine = $form->getData();

            $em->persist($agreementLine);
            $em->flush();
        } catch (\Exception $e) {
            return $this->composeErrorResponse($e);
        }

        return $this->json($agreementLine, Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['_linePanel'],
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
        ]);
    }

//    public function update(Request $request, AgreementLine $agreementLine, EntityManagerInterface $em): JsonResponse
//    {
//        // zapis produkcji
//        $retStatus = [];
//
//        // elementy produkcji przed zapisem
//        $productionOld = array_map(function($i) { return $i->getId(); }, $em->getRepository(Production::class)->findBy(['agreementLine' => $agreementLine]));
//        $productionIncoming = array_map(function($i) { return $i['id']; }, $request->request->get('productionData'));
//
//        try {
//            foreach ($request->request->get('productionData') as $prod) {
//
//                if (!$prod['id']) {
//                    $production = new Production();
//                    $production
//                        ->setCreatedAt(new \DateTime())
//                        ->setAgreementLine($agreementLine)
//                        ->setDepartmentSlug($prod['departmentSlug'])
//                    ;
//                } else {
//                    $production = $em->getRepository(Production::class)->find($prod['id']);
//                }
//                $oldStatus = $production->getStatus();
//                $production
//                    ->setStatus((int)$prod['status'])
//                    ->setUpdatedAt(new \DateTime());
//
//                if ($prod['title']) {
//                    $production->setTitle($prod['title']);
//                }
//
//                if ($prod['description']) {
//                    $production->setDescription($prod['description']);
//                }
//
//                if ($prod['dateStart']) {
//                    $production->setDateStart(new \DateTime($prod['dateStart']));
//                }
//
//                if ($prod['dateEnd']) {
//                    $production->setDateEnd(new \DateTime($prod['dateEnd']));
//                }
//
//                $em->persist($production);
//
//                if ($oldStatus != (int)$prod['status']) {
//                    $newStatus = new StatusLog();
//                    $newStatus
//                        ->setCurrentStatus((int)$prod['status'])
//                        ->setProduction($production)
//                        ->setCreatedAt(new \DateTime())
//                        ->setUser($this->getUser());
//                    $em->persist($newStatus);
//
//                    $retStatus[] = [
//                        'currentStatus' => (int)$newStatus->getCurrentStatus(),
//                        'createdAt' => $newStatus->getCreatedAt()->format('Y-m-d H:i:s'),
//                        'productionId' => $production->getId(),
//                    ];
//                }
//            }
//
//            // zapis agreement line
//            $line = $request->request->get('agreementLineData');
//
//            if ($line) {
//                if ($line['confirmedDate']) {
//                    $agreementLine->setConfirmedDate(new \DateTime($line['confirmedDate']));
//                }
//                $agreementLine->setDescription($line['description']);
//                $agreementLine->setStatus((int)$line['status']);
//                $em->persist($agreementLine);
//            }
//
//            // usunięcie
//            foreach (array_diff($productionOld, $productionIncoming) as $toDelete) {
//                $em->remove($em->getRepository(Production::class)->find($toDelete));
//            }
//
//            $em->flush();
//        } catch (\Exception $e) {
//            return $this->json([$e->getMessage()], RESPONSE::HTTP_UNPROCESSABLE_ENTITY);
//        }
//
//        return $this->json(['newStatuses' => $retStatus]);
//    }

    /**
     * @Route("/agreement_line/upload", name="agreement_line_upload", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     */
    public function uploadTest(Request $request)
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('sobczak-attach');
        $destination = $this->getParameter('kernel.project_dir') . '/public/uploads';

        $originalFileName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $newFileName = Urlizer::urlize($originalFileName) . '-' . uniqid() . '.' . $uploadedFile->guessExtension();
        dd($uploadedFile->move(
            $destination,
            $newFileName
        ));

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
