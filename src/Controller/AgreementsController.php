<?php

namespace App\Controller;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Product;
use App\Repository\AgreementLineRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Repository\AgreementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Date;

class AgreementsController extends AbstractController
{
    /**
     * @Route("/orders", name="agreements_show", methods={"GET"}, options={"expose"=true})
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {

        if ($request->query->get('add')) {
            $this->addFlash('success', 'Dodano zamówienie.');
        }

        if ($request->query->get('edit')) {
            $this->addFlash('success', 'Zapisano zamówienie.');
        }

        return $this->render('orders/orders_show.html.twig', [
            'title' => 'Lista zamówień'
        ]);
    }

    /**
     * @Route("/orders/add", name="orders_view_new", options={"expose"=true})
     */
    public function viewNewAgreement()
    {
        return $this->render('orders/order_single.html.twig', [
            'title' => 'Nowe zamówienie',
        ]);
    }

    /**
     * @Route("/orders/edit/{id}", name="orders_edit", options={"expose"=true})
     * @param Agreement $agreement
     * @return Response
     */
    public function viewEditAgreement(Agreement $agreement)
    {
        return $this->render('orders/order_single_edit.html.twig', [
            'title' => 'Edycja zamówienia',
            'agreement' => $agreement
        ]);
    }

    /**
     * @Route("/orders/fetch_single/{id}", name="orders_single_fetch", methods={"POST"}, options={"expose"=true})
     * @param Agreement $agreement
     * @return JsonResponse
     */
    public function fetchSingle(Agreement $agreement)
    {
        $returnData = [
            'customerId' => $agreement->getCustomer()->getId(),
            'orderNumber' => $agreement->getOrderNumber(),
            'roles' => $this->getUser()->getRoles()
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
     * @Route("/orders/fetch", name="orders_fetch", methods={"POST"})
     * @param Request $request
     * @return JsonResponse
     */
    public function fetch(Request $request, AgreementRepository $repository)
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
     * @Route("/orders/save", name="orders_add", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param CustomerRepository $customerRepository
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Exception
     */
    public function save(Request $request, CustomerRepository $customerRepository, ProductRepository $productRepository, EntityManagerInterface $em)
    {
        $customer = $customerRepository->find($request->request->getInt('customerId'));

        $agreement = new Agreement();
        $agreement
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime())
            ->setCustomer($customer)
            ->setOrderNumber($request->request->get('orderNumber'))
        ;

        $em->persist($agreement);
        $em->flush();

        if ($agreement->getId()) {

            foreach($request->request->get('products') as $productData) {
                $product = $productRepository->find($productData['productId']);

                $agreementLine = new AgreementLine();
                $agreementLine
                    ->setProduct($product)
                    ->setConfirmedDate(new \DateTime($productData['requiredDate']))
                    ->setDescription($productData['description'])
                    ->setAgreement($agreement)
                    ->setFactor($productData['factor'])
                    ->setDeleted(false)
                    ->setArchived(false)
                ;

                $em->persist($agreementLine);
            }

            $em->flush();

        }

        return new JsonResponse([$agreement->getId()]);
    }

    /**
     * @Route("/orders/patch/{agreement}", name="orders_patch", methods={"POST"}, options={"expose"=true})
     * @param Agreement $agreement
     * @param Request $request
     * @param CustomerRepository $customerRepository
     * @param AgreementLineRepository $agreementLineRepository
     * @param EntityManagerInterface $em
     * @return JsonResponse
     * @throws \Exception
     */
    public function edit(Agreement $agreement, Request $request,
                         CustomerRepository $customerRepository,
                         AgreementLineRepository $agreementLineRepository,
                         ProductRepository $productRepository,
                         EntityManagerInterface $em)
    {
        /**
         * 1. operujemy na agreement_line_id
         * 2. aktualizujemy klienta i numer zamówienia
         * 3. tworzymy zbiór wszystkich agreement line które należą do danego agreement
         * 4. z tych agreement które otrzymaliśmy, aktualizujemy te które mają id i usuwamy je ze zbioru
         * 5. z tych agreement które otrzymaliśmy i które nie mają id - dodajemy jako nowe
         * 6. jeśli zbiór jest niepusty to znaczy że te które zostały trzeba usunąć. usuwamy więc usuwając najpierw produkcję i historię zmian statusuów
         */

        /**
         * Stara tablica wszystkich pozycji zamówienia
         */
        $oldAgreementLineIds = [];
        foreach ($agreement->getAgreementLines() as $line) {
            $oldAgreementLineIds[] = $line->getId();
        }

        try {
            $customer = $customerRepository->find($request->request->getInt('customerId'));
            $orderNumber = $request->request->get('orderNumber');

            if (!$customer || !$orderNumber) {
                throw new \Exception('Wrong input data');
            }
            $agreement
                ->setCustomer($customer)
                ->setOrderNumber($orderNumber)
            ;

            $incomingLines = $request->request->get('products');
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

        return new JsonResponse();
    }

    /**
     * @Route("/orders/delete/{agreement}", name="orders_delete", methods={"POST"}, options={"expose"=true})
     * @param Agreement $agreement
     * @return JsonResponse
     */
    public function delete(Agreement $agreement, EntityManagerInterface $em)
    {
        $em->remove($agreement);
        $em->flush();
        return new JsonResponse();
    }

    /**
     * @Route("/orders/number/{id}", name="orders_number", methods={"POST"}, options={"expose"=true})
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function orderNumber(Request $request, Customer $customer, EntityManagerInterface $em)
    {
        $orders = $em->getRepository(Agreement::class)->getByCustomer($customer);
        $postalCode = preg_replace('/[^0-9]/', '', $customer->getPostalCode());

        return new JsonResponse([
            'next_number' => sprintf("%s-%02d", $postalCode, count($orders)+1),
        ]);
    }

    /**
     * @Route("/orders/number_validate/{number}", name="validate_number", methods={"POST"}, options={"expose"=true})
     * @param $number string
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return JsonResponse
     */
    public function validateNumber(Request $request, $number, EntityManagerInterface $em)
    {
        $orders = $em->getRepository(Agreement::class)->findBy(['orderNumber' => $number]);

        return new JsonResponse(['isValid' => !count($orders)]);
    }
}
