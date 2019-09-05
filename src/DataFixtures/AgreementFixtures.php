<?php

namespace App\DataFixtures;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Product;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class AgreementFixtures extends BaseFixture implements DependentFixtureInterface
{

    protected function loadData(ObjectManager $manager)
    {
        $this->createMany(Agreement::class, $this->agreementsNumber, function(Agreement $agreement, $count) use ($manager) {

            $agreementCreateDate = (new \DateTime('-1 month'))->modify(sprintf('+ %d days', $this->faker->numberBetween(1, 30)));
            $agreement
                ->setCreateDate($agreementCreateDate)
                ->setUpdateDate($agreementCreateDate)
                ->setCustomer($this->getRandomReference(Customer::class))
                ->setOrderNumber($this->faker->postcode() . '_' . $this->faker->numberBetween(1, 10))
                ->setStatus(0)
            ;

            $agreementLine = new AgreementLine();
            $product = $this->getRandomReference(Product::class);

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
            ;

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
