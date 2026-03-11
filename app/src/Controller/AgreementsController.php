<?php

namespace App\Controller;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Attachment;
use App\Entity\Customer;
use App\Module\AgreementLine\Event\AgreementLineWasCreatedEvent;
use App\Module\AgreementLine\Event\AgreementLineWasDeletedEvent;
use App\Module\AgreementLine\Event\AgreementLineWasUpdatedEvent;
use App\Module\Production\Command\CreateFactorCommand;
use App\Module\Production\Command\UpdateFactorCommand;
use App\Module\Production\DTO\FactorRatioDTO;
use App\Module\Production\Entity\FactorSource;
use App\Repository\AgreementLineRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Repository\AgreementRepository;
use App\Service\UploaderHelper;
use App\System\CommandBus;
use App\System\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class AgreementsController extends AbstractController
{
    #[Route(path: '/orders/add', name: 'orders_view_new', options: ['expose' => true])]
    public function viewNewAgreement(TranslatorInterface $t): Response
    {
        return $this->render('orders/order_single.html.twig', [
            'title' => $t->trans('Nowe zamówienie', [], 'agreements'),
        ]);
    }

    /**
     * @param Request $request
     * @param $status
     * @return Response
     */
    #[Route(path: '/orders/{status}', name: 'agreements_show', options: ['expose' => true], defaults: ['status' => 0], methods: ['GET'])]
    public function index($status, TranslatorInterface $t): Response
    {
        return $this->render('orders/orders_show.html.twig', [
            'title' => $t->trans('Lista zamówień', [], 'agreements'),
            'statuses' => AgreementLine::getStatuses(),
            'status' => $status
        ]);
    }

    /**
     * @param Agreement $agreement
     * @param TranslatorInterface $t
     * @return Response
     */
    #[Route(path: '/orders/edit/{id}', name: 'orders_edit', options: ['expose' => true])]
    public function viewEditAgreement(Agreement $agreement, TranslatorInterface $t): Response
    {
            return $this->render('orders/order_single_edit.html.twig', [
            'title' => $t->trans('Edycja zamówienia', [], 'agreements'),
            'agreement' => $agreement
        ]);
    }

    /**
     * @param Agreement $agreement
     * @param UploaderHelper $uploaderHelper
     * @return JsonResponse
     */
    #[Route(path: '/orders/fetch_single/{agreement}', name: 'orders_single_fetch', options: ['expose' => true], methods: ['POST'])]
    public function fetchSingle(Agreement $agreement, UploaderHelper $uploaderHelper): JsonResponse
    {
        $returnData = [
            'customerId' => $agreement->getCustomer()->getId(),
            'orderNumber' => $agreement->getOrderNumber(),
        ];
        foreach ($agreement->getAgreementLines() as $line) {
            if ($line->getArchived() || $line->getDeleted()) {
                continue;
            }
            $returnData['products'][] = [
                'id' => $line->getId(),
                'description' => $line->getDescription(),
                'productId' => $line->getProduct()->getId(),
                'requiredDate' => $line->getConfirmedDate()->format('Y-m-d'),
                'factor' => (float) $line->getFactor()
            ];
        }

        foreach ($agreement->getAttachments() as $attachment) {
            $returnData['attachments'][] = [
                'id' => $attachment->getId(),
                'name' => $attachment->getName(),
                'originalName' => $attachment->getOriginalName(),
                'extension' => $attachment->getExtension(),
                'url' => $uploaderHelper->getPublicPath($attachment->getPath()),
                'thumbnail' => $uploaderHelper->getPublicPathThumbnail($attachment->getPath()),
            ];
        }

        return new JsonResponse($returnData);
    }

    /**
     * @param Request $request
     * @param AgreementRepository $repository
     * @return JsonResponse
     */
    #[Route(path: '/orders/fetch', name: 'orders_fetch', methods: ['POST'])]
    public function fetch(Request $request, AgreementRepository $repository): JsonResponse
    {
        $agreements = $repository->getFiltered($request->request->all());

        return new JsonResponse(array_map(function ($record) {
            return [
                'id' => $record['id'],
                'createDate' => $record['createDate']->format('Y-m-d'),
                'customer' => $record['Customer']
            ];
        }, $agreements->getArrayResult()));

    }



    /**
     * @param Agreement $agreement
     * @param Request $request
     * @param CustomerRepository $customerRepository
     * @param AgreementLineRepository $agreementLineRepository
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $em
     * @param UploaderHelper $uploaderHelper
     * @param CommandBus $commandBus
     * @param EventBus $eventBus
     * @param TranslatorInterface $t
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route(path: '/orders/patch/{agreement}', name: 'orders_patch', options: ['expose' => true], methods: ['POST'])]
    public function edit(
        Agreement $agreement,
        Request $request,
        CustomerRepository $customerRepository,
        AgreementLineRepository $agreementLineRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $em,
        UploaderHelper $uploaderHelper,
        CommandBus $commandBus,
        EventBus $eventBus,
        TranslatorInterface $t
    ): JsonResponse
    {
        $requestData = $request->request->all();
        if (false === is_array($requestData['products'])) {
            $requestData['products'] = json_decode($requestData['products'], true);
        }

        $removedAttachmentIds = json_decode($requestData['removedAttachmentIds'] ?? '[]', true) ?? [];

        $oldAgreementLineIds = [];
        foreach ($agreement->getAgreementLines() as $line) {
            $oldAgreementLineIds[] = $line->getId();
        }

        try {
            $em->beginTransaction();

            $customerId = (int) ($requestData['customerId'] ?? 0);
            $orderNumber = (string) ($requestData['orderNumber'] ?? '');

            $customer = $customerRepository->find($customerId);
            if (!$customer || !$orderNumber) {
                throw new \Exception('Wrong input data');
            }

            $agreement
                ->setCustomer($customer)
                ->setOrderNumber($orderNumber)
            ;

            $incomingLines = $requestData['products'];
            if (empty($incomingLines)) {
                throw new \Exception('Wrong input data');
            }

            $factorCommands = [];
            $eventsCreated = [];
            $eventsUpdated = [];
            $eventsDeleted = [];

            foreach ($incomingLines as $incomingLine) {
                $productId = (int) ($incomingLine['productId'] ?? 0);
                $requiredDate = (string) ($incomingLine['requiredDate'] ?? '');
                $description = (string) ($incomingLine['description'] ?? '');
                $factor = (float) ($incomingLine['factor'] ?? 1.0);

                $isNew = false;
                if (isset($incomingLine['id']) && !empty($incomingLine['id'])) {
                    $line = $agreementLineRepository->find((int) $incomingLine['id']);

                    $idx = array_search($incomingLine['id'], $oldAgreementLineIds);
                    if (is_numeric($idx)) {
                        unset($oldAgreementLineIds[$idx]);
                    }
                } else {
                    $line = new AgreementLine();
                    $line->setDeleted(false)
                        ->setArchived(false)
                        ->setStatus(AgreementLine::STATUS_WAITING)
                    ;
                    $agreement->addAgreementLine($line);
                    $isNew = true;
                }

                $product = $productRepository->find($productId);
                if (!$product) {
                    throw new \Exception('Product not found');
                }

                $line->setConfirmedDate(new \DateTime($requiredDate));
                $line->setProduct($product);
                $line->setFactor($factor);
                $line->setDescription($description);

                $em->persist($line);

                $agreementLineFactor = $line->getFactorFromCollection();

                if ($isNew || !$agreementLineFactor) {
                    $em->flush();
                    $factorCommands[] = new CreateFactorCommand($line->getId(), new FactorRatioDTO(
                        FactorSource::AGREEMENT_LINE,
                        $line->getFactor(),
                    ));
                } else {
                    $factorCommands[] = new UpdateFactorCommand($line->getId(), new FactorRatioDTO(
                        FactorSource::AGREEMENT_LINE,
                        $line->getFactor(),
                        $agreementLineFactor->getId(),
                    ));
                }

                if ($isNew) {
                    $eventsCreated[] = new AgreementLineWasCreatedEvent($line->getId());
                } else {
                    $eventsUpdated[] = new AgreementLineWasUpdatedEvent($line->getId());
                }
            }

            // usuwanie załączników wskazanych przez frontend
            foreach ($removedAttachmentIds as $attachmentId) {
                $attachment = $em->find(Attachment::class, (int) $attachmentId);
                if ($attachment && $attachment->getAgreement()->getId() === $agreement->getId()) {
                    $em->remove($attachment);
                }
            }

            // nowe załączniki
            $rawFiles = $request->files->get('file');
            if (!is_array($rawFiles)) {
                $rawFiles = [$rawFiles];
            }
            $files = $uploaderHelper->getUploadedFiles($rawFiles);
            foreach ($files as $file) {
                $fileNames = $uploaderHelper->uploadAttachment($file);
                $attachment = new Attachment();
                $attachment->setAgreement($agreement);
                $attachment->setName($fileNames['newFileName']);
                $attachment->setOriginalName($fileNames['originalFileName']);
                $attachment->setExtension($fileNames['extension']);
                $em->persist($attachment);
            }

            $em->persist($agreement);
            $em->flush();

            // usuwanie linii
            if (!empty($oldAgreementLineIds)) {
                foreach ($oldAgreementLineIds as $agreementLineId) {
                    $line = $agreementLineRepository->find($agreementLineId);
                    $em->remove($line);
                    $eventsDeleted[] = new AgreementLineWasDeletedEvent($agreementLineId);
                }
                $em->flush();
            }

            $em->commit();

            // dispatch factor commands
            foreach ($factorCommands as $command) {
                $commandBus->dispatch($command);
            }

            // dispatch events
            foreach (array_merge($eventsCreated, $eventsUpdated, $eventsDeleted) as $event) {
                $eventBus->dispatch($event);
            }

            $this->addFlash('success', $t->trans('Zapisano zmiany.', [], 'agreements'));

            return new JsonResponse();

        } catch (\Exception $e) {
            $em->rollback();
            $this->addFlash('error', $t->trans('Błąd zapisu zamówienia.', [], 'agreements'));

            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @param Agreement $agreement
     * @param EntityManagerInterface $em
     * @param EventBus $eventBus
     * @return JsonResponse
     */
    #[Route(path: '/orders/delete/{agreement}', name: 'orders_delete', options: ['expose' => true], methods: ['POST'])]
    public function delete(
        Agreement $agreement,
        EntityManagerInterface $em,
        EventBus $eventBus,
    ): JsonResponse
    {
        $lines = array_map(
            fn (AgreementLine $line) => $line->getId(),
            $agreement->getAgreementLines()
        );
        $em->remove($agreement);
        $em->flush();

        foreach ($lines as $lineId) {
            $eventBus->dispatch(new AgreementLineWasDeletedEvent($lineId));
        }
        return new JsonResponse();
    }

    /**
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    #[Route(path: '/orders/number/{id}', name: 'orders_number', options: ['expose' => true], methods: ['POST'])]
    public function orderNumber(Customer $customer, EntityManagerInterface $em): JsonResponse
    {
        $orders = $em->getRepository(Agreement::class)->getByCustomerPostalCode($customer->getPostalCode());
        $postalCode = preg_replace('/[^0-9]/', '', $customer->getPostalCode());

        return new JsonResponse([
            'next_number' => sprintf("%s%d", $postalCode, count($orders)+1),
        ]);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    #[Route(path: '/orders/number_validate/', name: 'validate_number', options: ['expose' => true], methods: ['POST'])]
    public function validateNumber(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $orders = $em->getRepository(Agreement::class)->findBy(['orderNumber' => $request->request->get('number')]);

        return new JsonResponse(['isValid' => !count($orders)]);
    }
}
