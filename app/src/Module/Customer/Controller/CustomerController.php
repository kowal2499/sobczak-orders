<?php

namespace App\Module\Customer\Controller;

use App\Controller\BaseController;
use App\Entity\Customer;
use App\Entity\User;
use App\Module\Customer\Command\CreateCustomerCommand;
use App\Module\Customer\Command\CustomerData;
use App\Module\Customer\Command\UpdateCustomerCommand;
use App\Module\Customer\DTO\CustomerDTO;
use App\Module\Customer\Query\GetCustomersQuery;
use App\System\CommandBus;
use App\System\QueryBus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/customers')]
class CustomerController extends BaseController
{
    private const PAGE_SIZE = 20;

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_CUSTOMERS')]
    public function list(Request $request): JsonResponse
    {
        return $this->json($this->queryBus->query(new GetCustomersQuery(
            trim((string) $request->query->get('q', '')) ?: null,
            max(1, $request->query->getInt('page', 1)),
            self::PAGE_SIZE,
            $this->ownedCustomerIds(),
        )));
    }

    #[Route('/countries', methods: ['GET'])]
    public function countries(Request $request): JsonResponse
    {
        $countries = [];
        foreach (Countries::getNames($request->getLocale()) as $code => $name) {
            $countries[] = ['code' => $code, 'name' => $name];
        }

        return $this->json($countries);
    }

    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ASSIGNED_CUSTOMER', subject: 'customer')]
    public function single(Customer $customer): JsonResponse
    {
        return $this->json(CustomerDTO::fromEntity($customer));
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_CUSTOMERS')]
    public function create(Request $request): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new CreateCustomerCommand($this->payload($request)));
        } catch (ValidationFailedException $e) {
            return $this->violationResponse($e);
        }

        return $this->json(['success' => true], Response::HTTP_CREATED);
    }

    // No ROLE_CUSTOMERS here on purpose: a customer account may edit the data of its own customer.
    #[Route('/{id}', requirements: ['id' => '\d+'], methods: ['PUT'])]
    #[IsGranted('ASSIGNED_CUSTOMER', subject: 'customer')]
    public function update(Customer $customer, Request $request): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new UpdateCustomerCommand($customer->getId(), $this->payload($request)));
        } catch (ValidationFailedException $e) {
            return $this->violationResponse($e);
        }

        return $this->json(['success' => true]);
    }

    private function payload(Request $request): CustomerData
    {
        return CustomerData::fromArray(json_decode($request->getContent(), true) ?: []);
    }

    /**
     * @return int[]|null
     */
    private function ownedCustomerIds(): ?array
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->isGranted('ROLE_CUSTOMER')) {
            return null;
        }

        return array_values(array_filter(
            $user->getCustomers()->map(fn (?Customer $c) => $c?->getId())->toArray()
        ));
    }

    private function violationResponse(ValidationFailedException $exception): JsonResponse
    {
        $violation = $exception->getViolations()->get(0);

        return $this->json([
            'error' => (string) $violation->getMessage(),
            'field' => preg_replace('/^data\./', '', $violation->getPropertyPath()),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
