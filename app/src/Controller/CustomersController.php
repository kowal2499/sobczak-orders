<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CustomersController extends AbstractController
{
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
}
