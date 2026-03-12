<?php

namespace App\Module\Agreement\CommandHandler;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Attachment;
use App\Module\Agreement\Command\UpdateAgreementCommand;
use App\Module\AgreementLine\Event\AgreementLineWasCreatedEvent;
use App\Module\AgreementLine\Event\AgreementLineWasDeletedEvent;
use App\Module\AgreementLine\Event\AgreementLineWasUpdatedEvent;
use App\Module\Production\Command\CreateFactorCommand;
use App\Module\Production\Command\UpdateFactorCommand;
use App\Module\Production\DTO\FactorRatioDTO;
use App\Module\Production\Entity\FactorSource;
use App\Repository\AgreementLineRepository;
use App\Repository\AgreementRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Service\UploaderHelper;
use App\System\CommandBus;
use App\System\EventBus;
use Doctrine\ORM\EntityManagerInterface;

class UpdateAgreementCommandHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private AgreementRepository $agreementRepository,
        private CustomerRepository $customerRepository,
        private ProductRepository $productRepository,
        private AgreementLineRepository $agreementLineRepository,
        private UploaderHelper $uploaderHelper,
        private CommandBus $commandBus,
        private EventBus $eventBus,
    ) {
    }

    public function __invoke(UpdateAgreementCommand $command): void
    {
        $this->em->beginTransaction();

        try {
            $agreement = $this->getAgreement($command->agreementId);
            $customer = $this->getCustomer($command->customerId);

            $this->updateAgreement($agreement, $customer, $command->orderNumber);

            $oldAgreementLineIds = $this->getExistingLineIds($agreement);
            $processResult = $this->processAgreementLines($command, $agreement, $oldAgreementLineIds);

            $this->removeAttachments($command->removedAttachmentIds, $agreement);
            $this->addNewAttachments($command->attachments, $agreement);

            $this->em->persist($agreement);
            $this->em->flush();

            $linesToDelete = array_values($oldAgreementLineIds);
            $this->deleteRemovedLines($linesToDelete);

            $this->em->commit();

            $this->updateFactors($processResult['factorCommands']);
            $this->emitEvents(
                $processResult['eventsCreated'],
                $processResult['eventsUpdated'],
                $processResult['eventsDeleted']
            );
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    private function getAgreement(int $agreementId): Agreement
    {
        $agreement = $this->agreementRepository->find($agreementId);
        if (!$agreement) {
            throw new \InvalidArgumentException('Agreement not found');
        }
        return $agreement;
    }

    private function getCustomer(int $customerId): \App\Entity\Customer
    {
        $customer = $this->customerRepository->find($customerId);
        if (!$customer) {
            throw new \InvalidArgumentException('Customer not found');
        }
        return $customer;
    }

    private function updateAgreement(Agreement $agreement, \App\Entity\Customer $customer, string $orderNumber): void
    {
        $agreement
            ->setCustomer($customer)
            ->setOrderNumber($orderNumber)
        ;
    }

    private function getExistingLineIds(Agreement $agreement): array
    {
        $ids = [];
        foreach ($agreement->getAgreementLines() as $line) {
            $ids[] = $line->getId();
        }
        return $ids;
    }

    /**
     * @param UpdateAgreementCommand $command
     * @param Agreement $agreement
     * @param array $oldAgreementLineIds
     * @return array{factorCommands: array, eventsCreated: array, eventsUpdated: array, eventsDeleted: array}
     * @throws \Exception
     */
    private function processAgreementLines(
        UpdateAgreementCommand $command,
        Agreement $agreement,
        array &$oldAgreementLineIds
    ): array {
        $factorCommands = [];
        $eventsCreated = [];
        $eventsUpdated = [];
        $eventsDeleted = [];

        foreach ($command->products as $productData) {
            $productId = (int) ($productData['productId'] ?? 0);
            $requiredDate = (string) ($productData['requiredDate'] ?? '');
            $description = (string) ($productData['description'] ?? '');
            $factor = (float) ($productData['factor'] ?? 1.0);

            $product = $this->productRepository->find($productId);
            if (!$product) {
                throw new \InvalidArgumentException('Product not found');
            }

            $isNew = false;
            if (isset($productData['id']) && !empty($productData['id'])) {
                // Aktualizacja istniejącej linii
                $line = $this->agreementLineRepository->find((int) $productData['id']);
                if (!$line) {
                    throw new \InvalidArgumentException('AgreementLine not found');
                }

                $idx = array_search($productData['id'], $oldAgreementLineIds);
                if (is_numeric($idx)) {
                    unset($oldAgreementLineIds[$idx]);
                }
            } else {
                // Nowa linia
                $line = new AgreementLine();
                $line->setDeleted(false)
                    ->setArchived(false)
                    ->setStatus(AgreementLine::STATUS_WAITING)
                ;
                $agreement->addAgreementLine($line);
                $isNew = true;
            }

            $line->setConfirmedDate(new \DateTime($requiredDate));
            $line->setProduct($product);
            $line->setFactor($factor);
            $line->setDescription($description);

            $this->em->persist($line);

            $agreementLineFactor = $line->getFactorFromCollection();

            if ($isNew || !$agreementLineFactor) {
                $this->em->flush();
                $factorCommands[] = new CreateFactorCommand(
                    $line->getId(),
                    new FactorRatioDTO(
                        FactorSource::AGREEMENT_LINE,
                        $line->getFactor(),
                    )
                );
            } else {
                $factorCommands[] = new UpdateFactorCommand(
                    $line->getId(),
                    new FactorRatioDTO(
                        FactorSource::AGREEMENT_LINE,
                        $line->getFactor(),
                        $agreementLineFactor->getId(),
                    )
                );
            }

            if ($isNew) {
                $eventsCreated[] = new AgreementLineWasCreatedEvent($line->getId());
            } else {
                $eventsUpdated[] = new AgreementLineWasUpdatedEvent($line->getId());
            }
        }

        // Przygotowanie eventów dla usuniętych linii
        foreach ($oldAgreementLineIds as $agreementLineId) {
            $eventsDeleted[] = new AgreementLineWasDeletedEvent($agreementLineId);
        }

        return [
            'factorCommands' => $factorCommands,
            'eventsCreated' => $eventsCreated,
            'eventsUpdated' => $eventsUpdated,
            'eventsDeleted' => $eventsDeleted,
        ];
    }

    private function removeAttachments(array $removedAttachmentIds, Agreement $agreement): void
    {
        foreach ($removedAttachmentIds as $attachmentId) {
            $attachment = $this->em->find(Attachment::class, (int) $attachmentId);
            if ($attachment && $attachment->getAgreement()->getId() === $agreement->getId()) {
                $this->em->remove($attachment);
            }
        }
    }

    private function addNewAttachments(array $attachments, Agreement $agreement): void
    {
        foreach ($attachments as $file) {
            if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                continue;
            }

            $fileNames = $this->uploaderHelper->uploadAttachment($file);
            $attachment = new Attachment();
            $attachment->setAgreement($agreement);
            $attachment->setName($fileNames['newFileName']);
            $attachment->setOriginalName($fileNames['originalFileName']);
            $attachment->setExtension($fileNames['extension']);
            $this->em->persist($attachment);
        }
    }

    private function deleteRemovedLines(array $lineIds): void
    {
        if (empty($lineIds)) {
            return;
        }

        foreach ($lineIds as $agreementLineId) {
            $line = $this->agreementLineRepository->find($agreementLineId);
            if ($line) {
                $this->em->remove($line);
            }
        }
        $this->em->flush();
    }

    private function updateFactors(array $factorCommands): void
    {
        foreach ($factorCommands as $command) {
            $this->commandBus->dispatch($command);
        }
    }

    private function emitEvents(array $eventsCreated, array $eventsUpdated, array $eventsDeleted): void
    {
        foreach (array_merge($eventsCreated, $eventsUpdated, $eventsDeleted) as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}
