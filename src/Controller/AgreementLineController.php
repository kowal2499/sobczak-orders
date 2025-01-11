<?php

namespace App\Controller;

use App\Entity\AgreementLine;
use App\Entity\User;
use App\Form\AgreementLineType;
use App\Message\AssignTags;
use App\Message\Task\UpdateStatusCommand;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sluggable\Util\Urlizer;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AgreementLineRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;


class AgreementLineController extends BaseController
{
    /**
     * @Route("/agreement/line/{id}", name="agreement_line_details", methods={"GET"}, options={"expose"=true})
     * @param Request $request
     * @param AgreementLine $agreementLine
     * @param TranslatorInterface $t
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
     * @IsGranted("ROLE_PRODUCTION_VIEW")
     *
     * @Route("/api/agreement-line/fetch", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param AgreementLineRepository $repository
     * @param MessageBusInterface $messageBus
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
     * @IsGranted("ROLE_PRODUCTION_VIEW")
     *
     * @Route("/api/agreement-line/fetch-single/{id}", methods={"GET"})
     * @param int $id
     * @param Request $request
     * @param AgreementLineRepository $repository
     * @return JsonResponse
     */
    public function fetchSingle(int $id, Request $request, AgreementLineRepository $repository)
    {
        $result = $repository->getAllFiltered([
            'search' => [
                'agreementLineId' => $id
            ]
        ]);

        if (empty($result)) {
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }

        return $this->json(
            $result[0], Response::HTTP_OK, [], [
            AbstractNormalizer::GROUPS => ['_main', '_linePanel'],
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
        ]);
    }

    /**
     * @IsGranted("ROLE_PRODUCTION")
     *
     * @Route("/agreement_line/update/{id}", name="agreement_line_update", methods={"PUT"}, options={"expose"=true})
     * @param Request $request
     * @param AgreementLine $agreementLine
     * @param EntityManagerInterface $em
     * @param MessageBusInterface $messageBus
     * @param AgreementLineRepository $agreementLineRepository
     * @return JsonResponse
     */
    public function update(
        Request $request,
        AgreementLine $agreementLine,
        EntityManagerInterface $em,
        MessageBusInterface $messageBus,
        AgreementLineRepository $agreementLineRepository
    ): JsonResponse
    {
        $form = $this->createForm(AgreementLineType::class, $agreementLine);

        /** @var User $user */
        $user = $this->getUser();

        try {
            $this->processForm($request, $form);
            /** @var AgreementLine $agreementLine */
            $agreementLine = $form->getData();

            $em->persist($agreementLine);
            $em->flush();

            foreach ($agreementLine->getProductions() as $idx => $task) {
                $messageBus->dispatch(new UpdateStatusCommand(
                    $task->getId(),
                    $request->request->get('productions')[$idx]['status'])
                );
            }

            $messageBus->dispatch(new AssignTags(
                $request->request->get('tags') ?? [],
                $agreementLine->getId(),
                'production',
                $user->getId()
            ));

        } catch (\Exception $e) {
            return $this->composeErrorResponse($e);
        }

        return $this->json($agreementLineRepository->find($agreementLine->getId()), Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['_linePanel'],
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
        ]);
    }

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
