<?php

namespace App\Module\AgreementLine\CommandHandler;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Entity\TagAssignment;
use App\Module\AgreementLine\Command\UpdateAgreementLineRM;
use App\Module\AgreementLine\Entity\AddressRM;
use App\Module\AgreementLine\Entity\AgreementLineRM;
use App\Module\AgreementLine\Entity\AgreementRM;
use App\Module\AgreementLine\Entity\AttachmentRM;
use App\Module\AgreementLine\Entity\CustomerRM;
use App\Module\AgreementLine\Entity\ProductionRM;
use App\Module\AgreementLine\Entity\ProductRM;
use App\Module\AgreementLine\Entity\TagRM;
use App\Module\AgreementLine\Entity\UserRM;
use App\Module\AgreementLine\Repository\AgreementLineRMRepository;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\FactorCalculator;
use App\Repository\AgreementLineRepository;
use App\Service\UploaderHelper;
use Psr\Log\LoggerInterface;

class UpdateAgreementLineRMHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly AgreementLineRMRepository $modelRepository,
        private readonly FactorCalculator $factorCalculator,
        private readonly UploaderHelper $uploaderHelper,
    ) {
    }

    public function __invoke(UpdateAgreementLineRM $command): void
    {
        /** @var AgreementLine $agreementLine */
        $agreementLine = $this->agreementLineRepository->find($command->getAgreementLineId());
        if (!$agreementLine) {
            throw new \RuntimeException('AgreementLine not found with ID ' . $command->getAgreementLineId());
        }

        /** @var ?AgreementLineRM $model */
        $model = $this->modelRepository->find($command->getAgreementLineId());
        if (!$model) {
            $model = new AgreementLineRM($agreementLine->getId());
        }

        $model->setAgreementId($agreementLine->getAgreement()->getId());
        $model->setCustomerId($agreementLine->getAgreement()->getCustomer()->getId());
        $model->setAgreementCreateDate($agreementLine->getAgreement()->getCreateDate());
        $model->setStatus($agreementLine->getStatus());
        $model->setIsDeleted($agreementLine->getDeleted());
        $model->setIsArchived($agreementLine->getArchived());
        $model->setConfirmedDate($agreementLine->getConfirmedDate());
        $model->setProductionStartDate($agreementLine->getProductionStartDate());
        $model->setProductionEndDate($agreementLine->getProductionCompletionDate());
        $model->setUserName($agreementLine->getAgreement()->getUser()
            ? $agreementLine->getAgreement()->getUser()->getUserFullName()
            : null);
        $model->setOrderNumber($agreementLine->getAgreement()->getOrderNumber());
        $model->setCustomerName($this->getCustomerName($agreementLine));
        $model->setProductName($agreementLine->getProduct() ? $agreementLine->getProduct()->getName() : null);
        $model->setDescription($agreementLine->getDescription());
        $model->setFactor($agreementLine->getFactor());

        $model->setUser($this->getUser($agreementLine));
        $model->setCustomer($this->getCustomer($agreementLine));
        $model->setProduct($this->getProduct($agreementLine));
        $model->setAgreement($this->getAgreement($agreementLine));
        $model->setTags($this->getTags($agreementLine));
        $model->setAttachments($this->getAttachments($agreementLine));

        $productions = $this->getProductionsData($agreementLine);
        $model->setProductions(array_values($productions));
        $model->setHasProduction(count(array_values($productions)) > 0);

        $model->setDpt01StartDate(($productions['dpt01'] ?? null)?->getDateStart());
        $model->setDpt01EndDate(($productions['dpt01'] ?? null)?->getDateEnd());
        $model->setDpt02StartDate(($productions['dpt02'] ?? null)?->getDateStart());
        $model->setDpt02EndDate(($productions['dpt02'] ?? null)?->getDateEnd());
        $model->setDpt03StartDate(($productions['dpt03'] ?? null)?->getDateStart());
        $model->setDpt03EndDate(($productions['dpt03'] ?? null)?->getDateEnd());
        $model->setDpt04StartDate(($productions['dpt04'] ?? null)?->getDateStart());
        $model->setDpt04EndDate(($productions['dpt04'] ?? null)?->getDateEnd());
        $model->setDpt05StartDate(($productions['dpt05'] ?? null)?->getDateStart());
        $model->setDpt05EndDate(($productions['dpt05'] ?? null)?->getDateEnd());

        $model->setQ(
            trim(implode(' ', array_filter([
                $model->getOrderNumber(),
                $model->getCustomerName(),
                $model->getProductName(),
                $model->getUserName()
            ])))
        );
        $this->modelRepository->add($model);
        $this->logger->info('Updated AgreementLine read model', [
            'agreementLineId' => $command->getAgreementLineId(),
        ]);
    }

    private function getUser(AgreementLine $agreementLine): UserRM
    {
        $user = $agreementLine->getAgreement()->getUser();
        $model = new UserRM();
        if ($user) {
            $model->setId($user->getId());
            $model->setName($user->getUserFullName());
            $model->setEmail($user->getEmail());
        }
        return $model;
    }

    private function getProduct(AgreementLine $agreementLine): ProductRM
    {
        $product = $agreementLine->getProduct();
        return new ProductRM(
            $product->getId(),
            $product->getName(),
            $product->getFactor()
        );
    }

    private function getCustomer(AgreementLine $agreementLine): CustomerRM
    {
        $customer = $agreementLine->getAgreement()->getCustomer();
        $addressModel = new AddressRM(
            $customer->getStreet(),
            $customer->getStreetNumber(),
            $customer->getApartmentNumber(),
            $customer->getPostalCode(),
            $customer->getCity(),
            $customer->getCountry()
        );

        return new CustomerRM(
            $customer->getId(),
            $customer->getName(),
            $customer->getFirstName(),
            $customer->getLastName(),
            $customer->getPhone(),
            $customer->getEmail(),
            $addressModel
        );
    }

    private function getAgreement(AgreementLine $agreementLine): AgreementRM
    {
        $agreement = $agreementLine->getAgreement();
        return new AgreementRM(
            $agreement->getId(),
            $agreement->getCreateDate(),
            $agreement->getStatus(),
            $agreement->getOrderNumber()
        );
    }

    private function getTags(AgreementLine $agreementLine): array
    {
        return array_map(
            fn (TagAssignment $tag) => new TagRM(
                $tag->getTagDefinition()->getName(),
                $tag->getTagDefinition()->getIcon(),
                $tag->getTagDefinition()->getColor()
            ),
            $agreementLine->getTags()->toArray()
        );
    }

    /**
     * @param AgreementLine $agreementLine
     * @return AttachmentRM[]
     */
    private function getAttachments(AgreementLine $agreementLine): array
    {
        $data = [];
        foreach ($agreementLine->getAgreement()->getAttachments() as $attachment) {
            $data[] = new AttachmentRM(
                $attachment->getId(),
                $attachment->getName(),
                $attachment->getOriginalName(),
                $attachment->getExtension(),
                $attachment->getPath(),
                $this->uploaderHelper->getPublicPathThumbnail($attachment->getPath())
            );
        }

        return $data;
    }

    /**
     * @param AgreementLine $agreementLine
     * @return ProductionRM[]
     */
    private function getProductionsData(AgreementLine $agreementLine): array
    {
        $data = [];
        foreach ($agreementLine->getProductions() as $production) {
            $productionModel = new ProductionRM($production->getDepartmentSlug());
            $productionModel->setId($production->getId());
            $productionModel->setDateStart($production->getDateStart());
            $productionModel->setDateEnd($production->getDateEnd());
            $productionModel->setStatus($production->getStatus());
            $productionModel->setIsStartDelayed($production->getIsStartDelayed());
            $productionModel->setIsCompleted($production->getIsCompleted());
            $productionModel->setCompletedAt($production->getCompletedAt());

            if (in_array($production->getDepartmentSlug(), TaskTypes::getDefaultSlugs())) {
                $factorRatio = $this->factorCalculator->calculate(
                    $agreementLine,
                    $production->getDepartmentSlug(),
                    $agreementLine->getFactors()->toArray(),
                    FactorSource::FACTOR_ADJUSTMENT_RATIO
                );

                $factorBonus = $this->factorCalculator->calculate(
                    $agreementLine,
                    $production->getDepartmentSlug(),
                    $agreementLine->getFactors()->toArray(),
                    FactorSource::FACTOR_ADJUSTMENT_BONUS
                );

                $productionModel->setFactorRatio($factorRatio);
                $productionModel->setFactorBonus($factorBonus);
            }
            $data[$production->getDepartmentSlug()] = $productionModel;
        }
        return $data;
    }

    private function getCustomerName(AgreementLine $agreementLine): string
    {
        $customer = $agreementLine->getAgreement()->getCustomer();
        $person = implode(' ', array_filter([$customer->getFirstName(), $customer->getLastName()]));
        return trim($customer->getName() . ($person ? " ($person)" : ''));
    }
}
