<?php

namespace App\Module\Agreement\CommandHandler;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Attachment;
use App\Entity\Customer;
use App\Entity\Product;
use App\Module\Agreement\Command\LogAgreementUpdatedCommand;
use App\Module\Agreement\Command\UpdateAgreementCommand;
use App\Module\Agreement\Event\AgreementLineWasCreatedEvent;
use App\Module\Agreement\Event\AgreementLineWasDeletedEvent;
use App\Module\Agreement\Event\AgreementLineWasUpdatedEvent;
use App\Module\Production\Command\CreateFactorCommand;
use App\Module\Production\Command\UpdateFactorCommand;
use App\Module\Production\DTO\FactorRatioDTO;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Service\GhostProductionTaskService;
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
        private GhostProductionTaskService $ghostProductionTaskService,
    ) {
    }

    public function __invoke(UpdateAgreementCommand $command): void
    {
        $this->em->beginTransaction();

        try {
            $agreement = $this->getAgreement($command->agreementId);
            $customer = $this->getCustomer($command->customerId);

            $changes = $this->detectAgreementChanges($agreement, $customer, $command->orderNumber);

            $this->updateAgreement($agreement, $customer, $command->orderNumber);

            $oldAgreementLineIds = $this->getExistingLineIds($agreement);
            $processResult = $this->processAgreementLines($command, $agreement, $oldAgreementLineIds);
            $changes = array_merge($changes, $processResult['lineChanges']);

            $removedNames = $this->removeAttachments($command->removedAttachmentIds, $agreement);
            $addedNames = $this->addNewAttachments($command->attachments, $agreement);
            $changes = array_merge($changes, $this->buildAttachmentChanges($addedNames, $removedNames));

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

            if ($changes !== []) {
                $this->commandBus->dispatch(new LogAgreementUpdatedCommand($command->agreementId, $changes));
            }
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

    private function getCustomer(int $customerId): Customer
    {
        $customer = $this->customerRepository->find($customerId);
        if (!$customer) {
            throw new \InvalidArgumentException('Customer not found');
        }
        return $customer;
    }

    private function updateAgreement(Agreement $agreement, Customer $customer, string $orderNumber): void
    {
        $agreement
            ->setCustomer($customer)
            ->setOrderNumber($orderNumber)
        ;
    }

    /**
     * Captures agreement-level changes (customer, order number) before the entity is mutated.
     *
     * @return array<int, array<string, mixed>>
     */
    private function detectAgreementChanges(Agreement $agreement, Customer $customer, string $orderNumber): array
    {
        $changes = [];

        $oldCustomer = $agreement->getCustomer();
        if ($oldCustomer?->getId() !== $customer->getId()) {
            $changes[] = [
                'scope' => 'agreement',
                'field' => 'customer',
                'old' => $oldCustomer?->getName() ?? '',
                'new' => $customer->getName() ?? '',
            ];
        }

        $oldOrderNumber = (string) $agreement->getOrderNumber();
        if ($oldOrderNumber !== $orderNumber) {
            $changes[] = [
                'scope' => 'agreement',
                'field' => 'orderNumber',
                'old' => $oldOrderNumber,
                'new' => $orderNumber,
            ];
        }

        return $changes;
    }

    /**
     * @param array<int, string> $addedNames
     * @param array<int, string> $removedNames
     * @return array<int, array<string, mixed>>
     */
    private function buildAttachmentChanges(array $addedNames, array $removedNames): array
    {
        $changes = [];
        foreach ($addedNames as $name) {
            $changes[] = ['scope' => 'agreement', 'field' => 'attachmentAdded', 'value' => $name];
        }
        foreach ($removedNames as $name) {
            $changes[] = ['scope' => 'agreement', 'field' => 'attachmentRemoved', 'value' => $name];
        }

        return $changes;
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
     * @return array{factorCommands: array, eventsCreated: array, eventsUpdated: array,
     *               eventsDeleted: array, lineChanges: array}
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
        $lineChanges = [];

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

            $newConfirmedDate = new \DateTime($requiredDate);
            $oldConfirmedDate = $line->getConfirmedDate();
            $confirmedDateChanged = $isNew || $oldConfirmedDate === null
                || $oldConfirmedDate->format('Y-m-d') !== $newConfirmedDate->format('Y-m-d');

            $oldProduct = null;
            $oldFactor = null;
            $oldDescription = '';
            if (!$isNew) {
                $oldProduct = $line->getProduct();
                $oldFactor = $line->getFactor();
                $oldDescription = (string) $line->getDescription();
            }

            $line->setConfirmedDate($newConfirmedDate);
            $line->setProduct($product);
            $line->setFactor($factor);
            $line->setDescription($description);

            $this->em->persist($line);

            if (!$isNew) {
                $lineChanges = array_merge($lineChanges, $this->detectLineChanges(
                    $line,
                    $oldProduct,
                    $product,
                    $oldFactor,
                    $factor,
                    $oldConfirmedDate,
                    $newConfirmedDate,
                    $confirmedDateChanged,
                    $oldDescription,
                    $description,
                ));
            }

            if ($isNew) {
                $this->ghostProductionTaskService->createForAgreementLine($line);
            } elseif ($confirmedDateChanged) {
                $this->ghostProductionTaskService->regenerateForAgreementLine($line);
            }

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
            'lineChanges' => $lineChanges,
        ];
    }

    /**
     * Builds change descriptors for a single existing agreement line. The product name labels
     * the line so multiple lines within one order remain distinguishable in the activity log.
     *
     * @return array<int, array<string, mixed>>
     */
    private function detectLineChanges(
        AgreementLine $line,
        ?Product $oldProduct,
        Product $newProduct,
        ?float $oldFactor,
        float $newFactor,
        ?\DateTimeInterface $oldConfirmedDate,
        \DateTimeInterface $newConfirmedDate,
        bool $confirmedDateChanged,
        string $oldDescription,
        string $newDescription
    ): array {
        $lineId = $line->getId();
        $productName = $newProduct->getName() ?? '';
        $changes = [];

        if ($oldProduct?->getId() !== $newProduct->getId()) {
            $changes[] = $this->lineChange(
                $lineId,
                $productName,
                'product',
                $oldProduct?->getName() ?? '',
                $newProduct->getName() ?? '',
            );
        }

        if (!$this->factorsEqual($oldFactor, $newFactor)) {
            $changes[] = $this->lineChange(
                $lineId,
                $productName,
                'factor',
                $this->formatFactor($oldFactor),
                $this->formatFactor($newFactor),
            );
        }

        if ($confirmedDateChanged) {
            $changes[] = $this->lineChange(
                $lineId,
                $productName,
                'confirmedDate',
                $oldConfirmedDate?->format('Y-m-d') ?? '',
                $newConfirmedDate->format('Y-m-d'),
            );
        }

        if (trim($oldDescription) !== trim($newDescription)) {
            $changes[] = $this->lineChange($lineId, $productName, 'description', $oldDescription, $newDescription);
        }

        return $changes;
    }

    /**
     * @return array<string, mixed>
     */
    private function lineChange(int $lineId, string $productName, string $field, string $old, string $new): array
    {
        return [
            'scope' => 'line',
            'lineId' => $lineId,
            'productName' => $productName,
            'field' => $field,
            'old' => $old,
            'new' => $new,
        ];
    }

    private function factorsEqual(?float $a, float $b): bool
    {
        if ($a === null) {
            return false;
        }

        return abs($a - $b) < 1e-9;
    }

    private function formatFactor(?float $value): string
    {
        if ($value === null) {
            return '';
        }

        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    /**
     * @return array<int, string> original names of the attachments that were actually removed
     */
    private function removeAttachments(array $removedAttachmentIds, Agreement $agreement): array
    {
        $removedNames = [];
        foreach ($removedAttachmentIds as $attachmentId) {
            $attachment = $this->em->find(Attachment::class, (int) $attachmentId);
            if ($attachment && $attachment->getAgreement()->getId() === $agreement->getId()) {
                $removedNames[] = $attachment->getOriginalName() ?? $attachment->getName() ?? '';
                $this->em->remove($attachment);
            }
        }

        return $removedNames;
    }

    /**
     * @return array<int, string> original names of the attachments that were added
     */
    private function addNewAttachments(array $attachments, Agreement $agreement): array
    {
        $addedNames = [];
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
            $addedNames[] = $fileNames['originalFileName'];
        }

        return $addedNames;
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
