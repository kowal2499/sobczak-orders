<?php

namespace App\Controller;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Attachment;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
use App\Form\AgreementsType;
use App\Repository\AgreementLineRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Repository\AgreementRepository;
use App\Service\UploaderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Date;
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
     * @return JsonResponse
     */
    #[Route(path: '/orders/fetch_single/{id}', name: 'orders_single_fetch', options: ['expose' => true], methods: ['POST'])]
    public function fetchSingle(Agreement $agreement): JsonResponse
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
     * @param Request $request
     * @param CustomerRepository $customerRepository
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $em
     * @param UploaderHelper $uploaderHelper
     * @param TranslatorInterface $t
     * @param Security $security
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route(path: '/orders/save', name: 'orders_add', options: ['expose' => true], methods: ['POST'])]
    public function save(
        Request $request,
        CustomerRepository $customerRepository,
        ProductRepository $productRepository,
        EntityManagerInterface $em,
        UploaderHelper $uploaderHelper,
        TranslatorInterface $t,
        Security $security
    ): JsonResponse
    {
        $data = $request->request->all();

        if (false === is_array($data['products'])) {
            $data['products'] = json_decode($data['products'], true);
        }

        $customer = $customerRepository->find($data['customerId']);
        $agreement = new Agreement();
        $agreement
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime())
            ->setCustomer($customer)
            ->setUser($security->getUser())
            ->setOrderNumber($data['orderNumber'])
        ;

        $em->persist($agreement);

        foreach($data['products'] as $productData) {
            $product = $productRepository->find($productData['productId']);

            $agreementLine = new AgreementLine();
            $agreementLine
                ->setProduct($product)
                ->setConfirmedDate(new \DateTime($productData['requiredDate']))
                ->setDescription($productData['description'])
                ->setAgreement($agreement)
                ->setFactor($productData['factor'])
                ->setStatus(AgreementLine::STATUS_WAITING)  // początkowy status nowego zamówienia to 'oczekuje'
                ->setDeleted(false)
                ->setArchived(false)
            ;
            $em->persist($agreementLine);
        }

        // file upload logic
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
        $em->flush();

        if ($em->contains($agreement)) {
            $this->addFlash('success', $t->trans('Dodano nowe zamówienie.', [], 'agreements'));
        }
        else {
            $this->addFlash('error', $t->trans('Błąd dodawania zamówiena.', [], 'agreements'));
        }

        return new JsonResponse([$agreement->getId()]);
    }

    /**
     * @param Agreement $agreement
     * @param Request $request
     * @param CustomerRepository $customerRepository
     * @param AgreementLineRepository $agreementLineRepository
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Exception
     */
    #[Route(path: '/orders/patch/{agreement}', name: 'orders_patch', options: ['expose' => true], methods: ['POST'])]
    public function edit(Agreement $agreement, Request $request,
                         CustomerRepository $customerRepository,
                         AgreementLineRepository $agreementLineRepository,
                         ProductRepository $productRepository,
                         EntityManagerInterface $em,
                         UploaderHelper $uploaderHelper,
                         TranslatorInterface $t): JsonResponse
    {
        /**
         * 1. operujemy na agreement_line_id
         * 2. aktualizujemy klienta i numer zamówienia
         * 3. tworzymy zbiór wszystkich agreement line, które należą do danego agreement
         * 4. z tych agreement które otrzymaliśmy, aktualizujemy te, które mają id i usuwamy je ze zbioru
         * 5. z tych agreement które otrzymaliśmy i które nie mają id-dodajemy jako nowe
         * 6. jeśli zbiór jest niepusty, to znaczy, że te, które zostały trzeba usunąć. Usuwamy więc usuwając najpierw produkcję i historię zmian statusuów
         */

        $requestData = $request->request->all();
        if (false === is_array($requestData['products'])) {
            $requestData['products'] = json_decode($requestData['products'], true);
        }

        /**
         * Stara tablica wszystkich pozycji zamówienia
         */
        $oldAgreementLineIds = [];
        foreach ($agreement->getAgreementLines() as $line) {
            $oldAgreementLineIds[] = $line->getId();
        }

        try {
            $customer = $customerRepository->find($requestData['customerId']);
            $orderNumber = $requestData['orderNumber'];

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

            foreach ($incomingLines as $incomingLine) {

                if (isset($incomingLine['id']) && !empty($incomingLine['id'])) {
                    $line = $agreementLineRepository->find($incomingLine['id']);

                    $idx = array_search($incomingLine['id'], $oldAgreementLineIds);

                    if (is_numeric($idx)) {
                        unset($oldAgreementLineIds[$idx]);
                    }

                } else {
                    $line = new AgreementLine();
                    $line->setDeleted(false)
                        ->setAgreement($agreement)
                        ->setArchived(false)
                    ;
                }

                $line->setConfirmedDate(new \DateTime($incomingLine['requiredDate']));
                $line->setProduct($productRepository->find($incomingLine['productId']));
                $line->setFactor($incomingLine['factor']);
                $line->setDescription($incomingLine['description']);

                $em->persist($line);

            }

            // file upload logic
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

        } catch (Exception $e) {
            return new JsonResponse(null, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $em->persist($agreement);
        $em->flush();

        // usuwanie linii
        if (!empty($oldAgreementLineIds)) {

            foreach ($oldAgreementLineIds as $agreementLineId) {
                $line = $agreementLineRepository->find($agreementLineId);
                $em->remove($line);
            }
            $em->flush();
        }

        if ($em->contains($agreement)) {
            $this->addFlash('success', $t->trans('Zapisano zmiany.', [], 'agreements'));
        }

        return new JsonResponse();
    }

    /**
     * @param Agreement $agreement
     * @return JsonResponse
     */
    #[Route(path: '/orders/delete/{agreement}', name: 'orders_delete', options: ['expose' => true], methods: ['POST'])]
    public function delete(Agreement $agreement, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($agreement);
        $em->flush();
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
