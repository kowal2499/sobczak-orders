<?php

namespace App\Controller;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Module\Agreement\Event\AgreementLineWasDeletedEvent;
use App\Repository\AgreementRepository;
use App\Service\UploaderHelper;
use App\System\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
