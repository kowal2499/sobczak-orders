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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomersController extends AbstractController
{
    /**
     * @IsGranted("ROLE_CUSTOMERS")
     *
     * @param Request $request
     * @param CustomerRepository $repository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    #[Route(path: '/customers', name: 'customers_show')]
    public function customers(Request $request, CustomerRepository $repository, PaginatorInterface $paginator): Response
    {
        $customers = $repository->getWithSearch(['q' => $request->query->get('q')]);

        $pagination = $paginator->paginate(
            $customers,
            $request->query->getInt('page', 1),
            20
        );

            return $this->render('customers/customers_show.html.twig', [
            'customers' => $pagination,
            'search' => $request->query->get('q')
        ]);
    }

    /**
     *
     * @param Request $request
     * @param CustomerRepository $repository
     * @return JsonResponse
     */
    #[Route(path: '/customers/search', name: 'customers_search', options: ['expose' => true], methods: ['GET'])]
    public function search(Request $request, CustomerRepository $repository): JsonResponse
    {
        if (!$this->isGranted('ROLE_CUSTOMERS') && !$this->isGranted('ROLE_CUSTOMERS_LIMITED')) {
            throw $this->createAccessDeniedException();
        }

        $search = $request->query->all();

        if ($this->isGranted('ROLE_CUSTOMERS_LIMITED')) {
            $search['ownedBy'] = $this->getUser();
        }

        $customers = $repository->getWithSearch($search)->execute();

        return $this->json($customers, Response::HTTP_OK, [], [
            ObjectNormalizer::GROUPS => ['_main']
        ]);
    }

    /**
     * @IsGranted("ASSIGNED_CUSTOMER", subject="customer")
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    #[Route(path: '/customers/single_fetch/{id}', name: 'customers_single_fetch', options: ['expose' => true], methods: ['POST'])]
    public function fetchSingle(Customer $customer): JsonResponse
    {

//        $this->denyAccessUnlessGranted('ASSIGNED_CUSTOMER', $customer);

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
     * @IsGranted("ROLE_CUSTOMERS")
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     * @throws \Exception
     */
    #[Route(path: '/customers/new', name: 'customers_new', options: ['expose' => true])]
    public function add(Request $request, EntityManagerInterface $em, TranslatorInterface $t)
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

            $this->addFlash('success', $t->trans('Dodano nowego klienta.', [], 'customers'));
            return $this->redirectToRoute('customers_show');
        }

        return $this->render('customers/customer_single.html.twig', [
            'form' => $form->createView(),
            'title' => $t->trans('Nowy klient', [], 'customers'),
        ]);
    }

    /**
     * @IsGranted("ASSIGNED_CUSTOMER", subject="customer")
     *
     * @param Customer $customer
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $t
     * @return Response
     */
    #[Route(path: '/customers/edit/{id}', name: 'customers_edit', options: ['expose' => true])]
    public function edit(Customer $customer, Request $request, EntityManagerInterface $em, TranslatorInterface $t)
    {
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', $t->trans('Zmiany zostały zapisane.', [], 'customers'));

            if ($this->isGranted('ROLE_CUSTOMERS')) {
                return $this->redirectToRoute('customers_show');
            } else {
                return $this->redirectToRoute('agreements_show');
            }

        }

        return $this->render('customers/customer_single.html.twig', [
            'form' => $form->createView(),
            'title' => $t->trans('Edytuj dane klienta', [], 'customers'),
        ]);
    }
}
