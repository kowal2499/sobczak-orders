<?php

namespace App\Module\Agreement\Controller;

use App\Repository\AttachmentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/attachments')]
class AttachmentController extends AbstractController
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private string $uploadsPath
    ) {
    }

    #[Route('/{id}/download', name: 'attachment_download', methods: ['GET'])]
    public function download(int $id): BinaryFileResponse
    {
        return $this->serveFile($id, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    #[Route('/{id}/view', name: 'attachment_view', methods: ['GET'])]
    public function view(int $id): BinaryFileResponse
    {
        return $this->serveFile($id, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    private function serveFile(int $id, string $disposition): BinaryFileResponse
    {
        $attachment = $this->attachmentRepository->find($id);

        if (!$attachment) {
            throw new NotFoundHttpException('Attachment not found');
        }

        $filePath = $this->uploadsPath . '/' . $attachment->getPath();

        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('File not found on disk');
        }

        $response = new BinaryFileResponse($filePath);

        // Ustawienie nagłówków
        $fileName = $attachment->getOriginalName();
        if ($attachment->getExtension()) {
            $fileName .= '.' . $attachment->getExtension();
        }

        $response->setContentDisposition(
            $disposition,
            $fileName
        );

        // Ustawienie typu MIME
        $mimeType = $this->guessMimeType($attachment->getExtension());
        if ($mimeType) {
            $response->headers->set('Content-Type', $mimeType);
        }

        return $response;
    }

    private function guessMimeType(?string $extension): ?string
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'cs' => 'application/octet-stream', // .CS jako plik binarny
            'CS' => 'application/octet-stream',
        ];

        return $mimeTypes[strtolower($extension ?? '')] ?? 'application/octet-stream';
    }
}
