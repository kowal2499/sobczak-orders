<?php

namespace App\Module\Agreement\CommandHandler;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Attachment;
use App\Module\Agreement\Command\CreateAgreementCommand;
use App\Module\AgreementLine\Event\AgreementLineWasCreatedEvent;
use App\Module\Production\Command\CreateFactorCommand;
use App\Module\Production\DTO\FactorRatioDTO;
use App\Module\Production\Entity\FactorSource;
use App\Module\Tag\Command\AssignTagsCommand;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Service\UploaderHelper;
use App\System\CommandBus;
use App\System\EventBus;
use Doctrine\ORM\EntityManagerInterface;

class CreateAgreementCommandHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private CustomerRepository $customerRepository,
        private ProductRepository $productRepository,
        private UploaderHelper $uploaderHelper,
        private CommandBus $commandBus,
        private EventBus $eventBus,
    ) {}

    public function __invoke(CreateAgreementCommand $command): void
    {
        $this->em->beginTransaction();

        try {
            $customer = $this->customerRepository->find($command->customerId);
            if (!$customer) {
                throw new \InvalidArgumentException('Customer not found');
            }

            $agreement = new Agreement();
            $agreement
                ->setCreateDate(new \DateTime())
                ->setUpdateDate(new \DateTime())
                ->setCustomer($customer)
                ->setUser($this->em->getReference(\App\Entity\User::class, $command->userId))
                ->setOrderNumber($command->orderNumber)
            ;

            $this->em->persist($agreement);

            $capacityExceededLines = [];

            foreach ($command->products as $productData) {
                $productId = (int) ($productData['productId'] ?? 0);
                $requiredDate = (string) ($productData['requiredDate'] ?? '');
                $description = (string) ($productData['description'] ?? '');
                $factor = (float) ($productData['factor'] ?? 0);
                $isCapacityExceeded = (bool) ($productData['isCapacityExceeded'] ?? false);

                $product = $this->productRepository->find($productId);
                if (!$product) {
                    throw new \InvalidArgumentException('Product not found');
                }

                $agreementLine = new AgreementLine();
                $agreementLine
                    ->setProduct($product)
                    ->setConfirmedDate(new \DateTime($requiredDate))
                    ->setDescription($description)
                    ->setFactor($factor)
                    ->setStatus(AgreementLine::STATUS_WAITING)
                    ->setDeleted(false)
                    ->setArchived(false)
                ;
                $agreement->addAgreementLine($agreementLine);
                $this->em->persist($agreementLine);

                if ($isCapacityExceeded) {
                    $capacityExceededLines[] = $agreementLine;
                }
            }

            // Obsługa załączników
            foreach ($command->attachments as $file) {
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

            $this->em->flush();


            // Przypisanie tagów dla linii przekraczających moce produkcyjne
            foreach ($capacityExceededLines as $line) {
                $this->commandBus->dispatch(new AssignTagsCommand(
                    ['zlozone-pomimo-przekroczenia-mocy-produkcyjnych'],
                    $line->getId(),
                    'agreement-line',
                    $command->userId
                ));
            }

            // Utworzenie faktorów i emisja eventów
            foreach ($agreement->getAgreementLines() as $line) {
                $this->commandBus->dispatch(new CreateFactorCommand(
                    $line->getId(),
                    new FactorRatioDTO(
                        FactorSource::AGREEMENT_LINE,
                        $line->getFactor(),
                    )
                ));

                $this->eventBus->dispatch(new AgreementLineWasCreatedEvent($line->getId()));
            }

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}
