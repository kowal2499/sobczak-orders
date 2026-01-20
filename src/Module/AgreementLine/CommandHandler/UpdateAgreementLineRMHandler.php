<?php

namespace App\Module\AgreementLine\CommandHandler;

use App\Entity\AgreementLine;
use App\Entity\Definitions\TaskTypes;
use App\Module\AgreementLine\Command\UpdateAgreementLineRM;
use App\Module\AgreementLine\Entity\AddressRM;
use App\Module\AgreementLine\Entity\AgreementLineRM;
use App\Module\AgreementLine\Entity\AgreementRM;
use App\Module\AgreementLine\Entity\CustomerRM;
use App\Module\AgreementLine\Entity\ProductionRM;
use App\Module\AgreementLine\Entity\ProductRM;
use App\Module\AgreementLine\Entity\UserRM;
use App\Module\AgreementLine\Repository\AgreementLineRMRepository;
use App\Module\Production\Entity\FactorSource;
use App\Module\Production\Factor\FactorCalculator;
use App\Repository\AgreementLineRepository;
use Psr\Log\LoggerInterface;

class UpdateAgreementLineRMHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly AgreementLineRMRepository $modelRepository,
        private readonly FactorCalculator $factorCalculator,
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

        $model->setUser($this->getUser($agreementLine));
        $model->setCustomer($this->getCustomer($agreementLine));
        $model->setProduct($this->getProduct($agreementLine));
        $model->setAgreement($this->getAgreement($agreementLine));
        $model->setProductions($this->getProductionsData($agreementLine));

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
            $model->setFirstName($user->getFirstName());
            $model->setLastName($user->getLastName());
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
            $data[] = $productionModel;
        }
        return $data;
    }
}