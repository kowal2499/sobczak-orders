<?php

namespace App\Module\Agreement\CommandHandler;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Attachment;
use App\Module\Agreement\Command\CreateAgreementCommand;
use App\Module\Agreement\Event\AgreementLineWasCreatedEvent;
use App\Module\Agreement\Service\AgreementLineTaggingPolicy;
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
        private AgreementLineTaggingPolicy $taggingPolicy,
    ) {
    }

    public function __invoke(CreateAgreementCommand $command): void
    {
        $this->em->beginTransaction();

        try {
            $customer = $this->getCustomer($command->customerId);
            $agreement = $this->createAgreement($command, $customer);
            $linesToTag = $this->createAgreementLines($command, $agreement);
            $this->handleAttachments($command, $agreement);

            $this->em->flush();

            $this->assignTags($linesToTag, $command->userId);
            $this->createFactors($agreement);
            $this->emitAgreementLineCreatedEvents($agreement);

            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    private function getCustomer(int $customerId): \App\Entity\Customer
    {
        $customer = $this->customerRepository->find($customerId);
        if (!$customer) {
            throw new \InvalidArgumentException('Customer not found');
        }
        return $customer;
    }

    private function createAgreement(CreateAgreementCommand $command, \App\Entity\Customer $customer): Agreement
    {
        $agreement = new Agreement();
        $agreement
            ->setCreateDate(new \DateTime())
            ->setUpdateDate(new \DateTime())
            ->setCustomer($customer)
            ->setUser($this->em->getReference(\App\Entity\User::class, $command->userId))
            ->setOrderNumber($command->orderNumber)
        ;
        $this->em->persist($agreement);
        return $agreement;
    }

    /**
     * @param CreateAgreementCommand $command
     * @param Agreement $agreement
     * @return array Array of ['line' => AgreementLine, 'tags' => string[]]
     * @throws \Exception
     */
    private function createAgreementLines(CreateAgreementCommand $command, Agreement $agreement): array
    {
        $linesToTag = [];

        foreach ($command->products as $productData) {
            $productId = (int) ($productData['productId'] ?? 0);
            $requiredDate = (string) ($productData['requiredDate'] ?? '');
            $description = (string) ($productData['description'] ?? '');
            $factor = (float) ($productData['factor'] ?? 0);

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

            $tags = $this->taggingPolicy->getTagsForAgreementLine($productData);
            if (!empty($tags)) {
                $linesToTag[] = [
                    'line' => $agreementLine,
                    'tags' => $tags,
                ];
            }
        }

        return $linesToTag;
    }

    private function handleAttachments(CreateAgreementCommand $command, Agreement $agreement): void
    {
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
    }

    /**
     * @param array $linesToTag Array of ['line' => AgreementLine, 'tags' => string[]]
     */
    private function assignTags(array $linesToTag, int $userId): void
    {
        foreach ($linesToTag as $item) {
            $this->commandBus->dispatch(new AssignTagsCommand(
                $item['tags'],
                $item['line']->getId(),
                'agreement-line',
                $userId
            ));
        }
    }

    private function createFactors(Agreement $agreement): void
    {
        foreach ($agreement->getAgreementLines() as $line) {
            $this->commandBus->dispatch(new CreateFactorCommand(
                $line->getId(),
                new FactorRatioDTO(
                    FactorSource::AGREEMENT_LINE,
                    $line->getFactor(),
                )
            ));
        }
    }

    private function emitAgreementLineCreatedEvents(Agreement $agreement): void
    {
        foreach ($agreement->getAgreementLines() as $line) {
            $this->eventBus->dispatch(new AgreementLineWasCreatedEvent($line->getId()));
        }
    }
}
