<?php

namespace App\Controller;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Product;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Repository\AgreementRepository;
use Doctrine\ORM\EntityManagerInterface;
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
