<?php

namespace App\Module\Agreement\Controller;

use App\Entity\Agreement;
use App\Module\Agreement\Command\CreateAgreementCommand;
use App\Module\Agreement\Command\UpdateAgreementCommand;
use App\Service\UploaderHelper;
use App\System\CommandBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        if ($errorResponse = $this->validatePostSize($request)) {
            return $errorResponse;
        }

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

            if ($errorResponse = $this->validateAttachments($attachments)) {
                return $errorResponse;
            }
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

    /**
     * Aktualizacja istniejącego zamówienia
     */
    #[Route('/patch/{agreement}', name: 'orders_patch', options: ['expose' => true], methods: ['POST'])]
    public function update(Agreement $agreement, Request $request): JsonResponse
    {
        if ($errorResponse = $this->validatePostSize($request)) {
            return $errorResponse;
        }

        $data = $request->request->all();

        // Parsowanie products jeśli przyszły jako JSON string
        if (false === is_array($data['products'] ?? null)) {
            $data['products'] = json_decode($data['products'], true);
        }

        // Parsowanie removedAttachmentIds
        $removedAttachmentIds = json_decode($data['removedAttachmentIds'] ?? '[]', true) ?? [];

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

            if ($errorResponse = $this->validateAttachments($attachments)) {
                return $errorResponse;
            }
        }

        try {
            $command = new UpdateAgreementCommand(
                agreementId: $agreement->getId(),
                customerId: $customerId,
                orderNumber: $orderNumber,
                products: $products,
                userId: $this->security->getUser()->getId(),
                attachments: $attachments,
                removedAttachmentIds: $removedAttachmentIds,
            );

            $this->commandBus->dispatch($command);

            return new JsonResponse(['success' => true], Response::HTTP_OK);
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

    private function validatePostSize(Request $request): ?JsonResponse
    {
        $maxBytes = $this->iniSizeToBytes((string) ini_get('post_max_size'));
        if ($maxBytes <= 0) {
            return null;
        }

        $contentLength = (int) $request->headers->get('Content-Length', '0');
        if ($contentLength <= 0 || $contentLength <= $maxBytes) {
            return null;
        }

        return $this->json(
            [
                'error' => sprintf(
                    'Request size exceeds server limit (max %s).',
                    ini_get('post_max_size'),
                ),
            ],
            Response::HTTP_REQUEST_ENTITY_TOO_LARGE,
        );
    }

    private function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ('' === $value) {
            return 0;
        }

        $unit = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => (int) $value,
        };
    }

    /**
     * @param UploadedFile[] $attachments
     */
    private function validateAttachments(array $attachments): ?JsonResponse
    {
        $errors = [];
        foreach ($attachments as $attachment) {
            if (UPLOAD_ERR_OK === $attachment->getError()) {
                continue;
            }

            $errors[] = [
                'filename' => $attachment->getClientOriginalName(),
                'message' => $attachment->getErrorMessage(),
            ];
        }

        if (empty($errors)) {
            return null;
        }

        $summary = implode(', ', array_map(static fn (array $e): string => $e['filename'], $errors));

        return $this->json(
            [
                'error' => sprintf('Rejected attachments: %s', $summary),
                'errors' => $errors,
            ],
            Response::HTTP_BAD_REQUEST,
        );
    }
}
