<?php

namespace App\Module\Agreement\Controller;

use App\Module\Agreement\Command\CreateAgreementCommand;
use App\Service\UploaderHelper;
use App\System\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/orders')]
class AgreementController extends AbstractController
{
    public function __construct(
        private CommandBus $commandBus,
        private Security $security,
        private UploaderHelper $uploaderHelper,
    ) {
    }

    /**
     * Utworzenie nowego zamówienia
     */
    #[Route('/save', name: 'orders_add', options: ['expose' => true], methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        $data = $request->request->all();

        // Parsowanie products jeśli przyszły jako JSON string
        if (false === is_array($data['products'] ?? null)) {
            $data['products'] = json_decode($data['products'], true);
        }

        // Walidacja danych wejściowych
        $customerId = (int) ($data['customerId'] ?? 0);
        $orderNumber = (string) ($data['orderNumber'] ?? '');
        $products = (array) ($data['products'] ?? []);

        if ($customerId <= 0) {
            return $this->json(
                ['error' => 'Invalid customer ID'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (empty($orderNumber)) {
            return $this->json(
                ['error' => 'Order number is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (empty($products)) {
            return $this->json(
                ['error' => 'At least one product is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Przygotowanie załączników
        $attachments = [];
        $rawFiles = $request->files->get('file');
        if ($rawFiles) {
            if (!is_array($rawFiles)) {
                $rawFiles = [$rawFiles];
            }
            $attachments = $this->uploaderHelper->getUploadedFiles($rawFiles);
        }

        try {
            $command = new CreateAgreementCommand(
                customerId: $customerId,
                orderNumber: $orderNumber,
                products: $products,
                userId: $this->security->getUser()->getId(),
                attachments: $attachments,
            );

            $this->commandBus->dispatch($command);

            return new JsonResponse(['success' => true], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'An unexpected error occurred'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
