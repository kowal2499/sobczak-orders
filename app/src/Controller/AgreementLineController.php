<?php

namespace App\Controller;

use App\Entity\AgreementLine;
use App\Entity\User;
use App\Form\AgreementLineType;
use App\Message\Task\UpdateStatusCommand;
use App\Module\Agreement\ActivityLog\AgreementActivityLogType;
use App\Module\Agreement\Command\LogAgreementLineActivityCommand;
use App\Module\Agreement\Command\LogProductionDateChangedCommand;
use App\Module\Agreement\Event\AgreementLineWasUpdatedEvent;
use App\Module\Tag\Command\AssignTagsCommand;
use App\Repository\AgreementLineRepository;
use App\System\CommandBus;
use App\System\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Gedmo\Sluggable\Util\Urlizer;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;


class AgreementLineController extends BaseController
{
    /**
     * @param Request $request
     * @param AgreementLine $agreementLine
     * @param TranslatorInterface $t
     * @return Response
     */
    #[Route(path: '/agreement/line/{id}', name: 'agreement_line_details', options: ['expose' => true], methods: ['GET'])]
    public function details(Request $request, AgreementLine $agreementLine, TranslatorInterface $t): Response
    {
        $taskStatuses = array_map(function ($value) use ($t) {
            return $t->trans($value, [], 'agreements');
        }, AgreementLine::getStatuses());

        return $this->render('agreement_line/order_details.html.twig', [
            'title' => $t->trans('Panel zamówienia', [], 'agreements'),
            'agreementLineId' => $request->attributes->getInt('id'),
            'taskStatuses' => $taskStatuses
        ]);
    }

    /**
     * @IsGranted("ROLE_PRODUCTION_VIEW")
     *
     * @param Request $request
     * @param AgreementLineRepository $repository
     * @param PaginatorInterface $paginator
     * @return JsonResponse
     */
    #[Route(path: '/api/agreement-line/fetch', options: ['expose' => true], methods: ['POST'])]
    public function fetch(Request $request, AgreementLineRepository $repository, PaginatorInterface $paginator): JsonResponse
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
     * @param int $id
     * @param Request $request
     * @param AgreementLineRepository $repository
     * @return JsonResponse
     */
    #[Route(path: '/api/agreement-line/fetch-single/{id}', methods: ['GET'])]
    public function fetchSingle(int $id, AgreementLineRepository $repository): JsonResponse
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
     * @param Request $request
     * @param AgreementLine $agreementLine
     * @param EntityManagerInterface $em
     * @param MessageBusInterface $messageBus
     * @param AgreementLineRepository $agreementLineRepository
     * @param EventBus $eventBus
     * @return JsonResponse
     */
    #[Route(path: '/agreement_line/update/{id}', name: 'agreement_line_update', options: ['expose' => true], methods: ['PUT'])]
    public function update(
        Request $request,
        AgreementLine $agreementLine,
        EntityManagerInterface $em,
        MessageBusInterface $messageBus,
        AgreementLineRepository $agreementLineRepository,
        EventBus $eventBus,
        TranslatorInterface $translator,
        CommandBus $commandBus,
    ): JsonResponse
    {
        $oldStatus = $agreementLine->getStatus();
        $oldProductionDates = $this->snapshotProductionDates($agreementLine);
        $form = $this->createForm(AgreementLineType::class, $agreementLine);

        /** @var User $user */
        $user = $this->getUser();

        try {
            $this->processForm($request, $form);
            /** @var AgreementLine $agreementLine */
            $agreementLine = $form->getData();

            $agreementLine->setStatus($oldStatus);

            $em->persist($agreementLine);
            $em->flush();

            $this->logProductionDateChanges($agreementLine, $oldProductionDates, $commandBus);

            $payload = json_decode($request->getContent(), true) ?? [];
            $productions = $payload['productions'] ?? [];

            $productionsBySlug = [];
            foreach ($agreementLine->getProductions() as $prod) {
                $productionsBySlug[$prod->getDepartmentSlug()] = $prod;
            }

            foreach ($productions as $record) {
                $production = $productionsBySlug[$record['departmentSlug']] ?? null;
                if ($production !== null && $production->isGhost() && isset($record['status']) && (string) $record['status'] !== $production->getStatus()) {
                    throw new \LogicException($translator->trans('Nie można zmieniać statusów zadań w trybie prognozy. Najpierw zleć produkcję.', [], 'agreements'));
                }
            }

            foreach ($productions as $record) {
                $production = $productionsBySlug[$record['departmentSlug']] ?? null;
                if ($production === null) {
                    continue;
                }

                $messageBus->dispatch(new UpdateStatusCommand(
                    $production->getId(),
                    $record['status']
                ));
            }

            $tags = $payload['tags'] ?? [];
            $messageBus->dispatch(new AssignTagsCommand(
                $tags,
                $agreementLine->getId(),
                'agreement-line',
                $user->getId()
            ));

            $eventBus->dispatch(new AgreementLineWasUpdatedEvent($agreementLine->getId()));

        } catch (Exception $e) {
            return $this->composeErrorResponse($e);
        }

        return $this->json($agreementLineRepository->find($agreementLine->getId()), Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['_linePanel'],
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
        ]);
    }

    /**
     * Captures dateStart/dateEnd of each existing production keyed by production id,
     * so date changes applied by the form can be detected after flush.
     *
     * @return array<int, array{start: ?string, end: ?string}>
     */
    private function snapshotProductionDates(AgreementLine $agreementLine): array
    {
        $snapshot = [];
        foreach ($agreementLine->getProductions() as $production) {
            $snapshot[$production->getId()] = [
                'start' => $production->getDateStart()?->format('Y-m-d'),
                'end' => $production->getDateEnd()?->format('Y-m-d'),
            ];
        }
        return $snapshot;
    }

    /**
     * Emits a separate log per changed date (start / end), carrying the old and new value.
     * Both changed → two logs.
     *
     * @param array<int, array{start: ?string, end: ?string}> $oldDates
     */
    private function logProductionDateChanges(
        AgreementLine $agreementLine,
        array $oldDates,
        CommandBus $commandBus,
    ): void {
        foreach ($agreementLine->getProductions() as $production) {
            $old = $oldDates[$production->getId()] ?? null;
            if ($old === null) {
                continue; // newly added production — not a date change
            }

            $newStart = $production->getDateStart()?->format('Y-m-d');
            $newEnd = $production->getDateEnd()?->format('Y-m-d');

            if ($old['start'] !== $newStart) {
                $commandBus->dispatch(new LogProductionDateChangedCommand(
                    $production->getId(),
                    AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_START_CHANGED,
                    $old['start'],
                    $newStart,
                ));
            }

            if ($old['end'] !== $newEnd) {
                $commandBus->dispatch(new LogProductionDateChangedCommand(
                    $production->getId(),
                    AgreementActivityLogType::AGREEMENT_LINE_PRODUCTION_DATE_END_CHANGED,
                    $old['end'],
                    $newEnd,
                ));
            }
        }
    }

    /**
     * @param Request $request
     */
    #[Route(path: '/agreement_line/upload', name: 'agreement_line_upload', options: ['expose' => true], methods: ['POST'])]
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
     * @param AgreementLine $agreementLine
     * @param $statusId
     * @param EntityManagerInterface $em
     * @param EventBus $eventBus
     * @return JsonResponse
     */
    #[Route(path: '/agreement_line/archive/{id}/{statusId}', name: 'agreement_line_archive', options: ['expose' => true], methods: ['POST'])]
    public function setStatus(
        AgreementLine $agreementLine,
        $statusId,
        EntityManagerInterface $em,
        EventBus $eventBus,
        CommandBus $commandBus,
    ): JsonResponse
    {
        $agreementLine->setStatus((int)$statusId);
        $em->flush();
        $eventBus->dispatch(new AgreementLineWasUpdatedEvent($agreementLine->getId()));

        $logType = AgreementActivityLogType::forStatus((int) $statusId);
        if ($logType !== null) {
            $commandBus->dispatch(new LogAgreementLineActivityCommand($agreementLine->getId(), $logType));
        }

        return $this->json([]);
    }

    /**
     * @isGranted("ROLE_ADMIN")
     *
     * @param AgreementLine $agreementLine
     * @param EntityManagerInterface $em
     * @param EventBus $eventBus
     * @return JsonResponse
     */
    #[Route(path: '/agreement_line/delete/{agreementLine}', name: 'agreement_line_delete', options: ['expose' => true], methods: ['POST'])]
    public function delete(
        AgreementLine $agreementLine,
        EntityManagerInterface $em,
        EventBus $eventBus,
        CommandBus $commandBus,
    ): JsonResponse
    {
        $agreementLine->setDeleted(true);
        $em->flush();
        $eventBus->dispatch(new AgreementLineWasUpdatedEvent($agreementLine->getId()));
        $commandBus->dispatch(new LogAgreementLineActivityCommand(
            $agreementLine->getId(),
            AgreementActivityLogType::AGREEMENT_LINE_DELETED,
        ));
        return new JsonResponse();
    }
}
