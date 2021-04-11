<?php

namespace App\DataFixtures;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\TagAssignment;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class AgreementFixtures extends BaseFixture implements DependentFixtureInterface
{
    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(Agreement::class, $this->agreementsNumber, function(Agreement $agreement, $count) use ($manager) {
            /** @var Product $product */
            $product = $this->getRandomReference(Product::class);
            /** @var Customer $customer */
            $customer = $this->getRandomReference(Customer::class);

            $agreementCreateDate = (new \DateTime('-1 month'))->modify(sprintf('+ %d days', $this->faker->numberBetween(1, 30)));
            $agreement
                ->setCreateDate($agreementCreateDate)
                ->setUpdateDate($agreementCreateDate)
                ->setCustomer($customer)
                ->setOrderNumber($this->faker->postcode() . '_' . $this->faker->numberBetween(1, 10))
                ->setStatus(0);

            $agreementLine = new AgreementLine();

            $confirmedDate = clone $agreementCreateDate;
            $confirmedDate = $confirmedDate->modify(sprintf('+ %d days', $this->faker->numberBetween(1, 50)));

            $agreementLine
                ->setProduct($product)
                ->setArchived(0)
                ->setDeleted(0)
                ->setDescription($this->faker->boolean ? $this->faker->paragraph : $this->faker->sentences(3, true))
                ->setConfirmedDate($confirmedDate)
                ->setFactor($this->faker->boolean ? $product->getFactor() : $product->getFactor() * 1.05)
                ->setAgreement($agreement)
                ->setStatus(AgreementLine::STATUS_WAITING);

            $manager->persist($agreementLine);

        });
        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            CustomerFixtures::class,
            ProductFixtures::class
        ];
    }
}
