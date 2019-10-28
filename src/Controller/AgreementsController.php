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
use Symfony\Contracts\Translation\TranslatorInterface;

class AgreementsController extends AbstractController
{
    /**
     * @Route("/orders/add", name="orders_view_new", options={"expose"=true})
     */
    public function viewNewAgreement(TranslatorInterface $t)
    {
        return $this->render('orders/order_single.html.twig', [
            'title' => $t->trans('Nowe zamówienie', [], 'agreements'),
        ]);
    }

    /**
     * @Route("/orders/{status}", name="agreements_show", methods={"GET"}, options={"expose"=true}, defaults={"status" = 0})
     * @param Request $request
     * @param $status
     * @return Response
     */
    public function index(Request $request, $status, TranslatorInterface $t)
    {
        return $this->render('orders/orders_show.html.twig', [
            'title' => $t->trans('Lista zamówień', [], 'agreements'),
            'statuses' => AgreementLine::getStatuses(),
            'status' => $status
        ]);
    }

    /**
     * @Route("/orders/edit/{id}", name="orders_edit", options={"expose"=true})
     * @param Agreement $agreement
     * @return Response
     */
    public function viewEditAgreement(Agreement $agreement, TranslatorInterface $t)
    {
            return $this->render('orders/order_single_edit.html.twig', [
            'title' => $t->trans('Edycja zamówienia', [], 'agreements'),
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
    public function save(Request $request, CustomerRepository $customerRepository, ProductRepository $productRepository, EntityManagerInterface $em, TranslatorInterface $t)
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

        foreach($request->request->get('products') as $productData) {
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
                         EntityManagerInterface $em,
                         TranslatorInterface $t)
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

        if ($em->contains($agreement)) {
            $this->addFlash('success', $t->trans('Zapisano zmiany.', [], 'agreements'));
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
