<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\CustomerType;
use App\Form\ProductType;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CustomersController extends AbstractController
{
    /**
     * @Route("/customers", name="customers_show")
     * @param Request $request
     * @param CustomerRepository $repository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function customers(Request $request, CustomerRepository $repository, PaginatorInterface $paginator)
    {

        $customers = $repository->getWithSearch($request->query->get('q'));

        $pagination = $paginator->paginate(
            $customers,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('customers/customers_show.html.twig', [
            'customers' => $pagination,
            'search' => $request->query->get('q')
        ]);
    }

    /**
     * @Route("/customers/search", name="customers_search", methods={"GET"}, options={"expose"=true})
     * @param Request $request
     * @param CustomerRepository $repository
     * @return JsonResponse
     */
    public function search(Request $request, CustomerRepository $repository)
    {
        $customers = $repository->getWithSearch($request->query->get('q'));

        return new JsonResponse($customers->getArrayResult());
    }

    /**
     * @Route("/customers/single_fetch/{id}", name="customers_single_fetch", methods={"POST"}, options={"expose"=true})
     * @param Customer $customer
     * @return JsonResponse
     */
    public function fetchSingle(Customer $customer) {
        return new JsonResponse([
            'apartment_number' => $customer->getApartmentNumber(),
            'city' => $customer->getCity(),
            'country' => $customer->getCountry(),
            'email' => $customer->getEmail(),
            'first_name' => $customer->getFirstName(),
            'id' => $customer->getId(),
            'last_name' => $customer->getLastName(),
            'name' => $customer->getName(),
            'phone' => $customer->getPhone(),
            'postal_code' => $customer->getPostalCode(),
            'street' => $customer->getStreet(),
            'street_number' => $customer->getStreetNumber()
        ]);
    }

    /**
     * @Route("/customers/new", name="customers_new", options={"expose"=true})
     * @param EntityManagerInterface $em
     * @return Response
     * @throws \Exception
     */
    public function add(Request $request, EntityManagerInterface $em)
    {
        $customer = new Customer();
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customer = $form->getData();
            $customer->setCreateDate(new \DateTime('now'));
            $customer->setUpdateDate(new \DateTime('now'));
            $em->persist($customer);
            $em->flush();

            $this->addFlash('success', 'Dodano nowego klienta.');
            return $this->redirectToRoute('customers_show');
        }

        return $this->render('customers/customer_single.html.twig', [
            'form' => $form->createView(),
            'title' => 'Nowy klient',
        ]);
    }

    /**
     * @Route("/customers/edit/{id}", name="customers_edit", options={"expose"=true})
     * @param Customer $customer
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function edit(Customer $customer, Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Zmiany zostały zapisane.');
            return $this->redirectToRoute('customers_show');
        }

        return $this->render('customers/customer_single.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edytuj dane klienta',
        ]);
    }
}
