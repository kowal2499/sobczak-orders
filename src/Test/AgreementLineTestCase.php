<?php


namespace App\Test;


use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\Production;
use Faker\Factory;

class AgreementLineTestCase extends KernelTestCase
{
    protected $em;
    protected $agreementLineRepository;
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getDBConnection()->beginTransaction();
        $this->em = $this->getManager();
        $this->agreementLineRepository = $this->em->getRepository(AgreementLine::class);


        $this->faker = Factory::create('pl_PL');

        $customer = new Customer();
        $customer
            ->setFirstName($this->faker->firstName())
            ->setLastName($this->faker->lastName())
            ->setName($this->faker->company())
            ->setCity($this->faker->city())
            ->setCountry('PL')
            ->setCreateDate($this->faker->dateTimeBetween('-123 days', '-1 days'))
            ->setUpdateDate(new \DateTime())
        ;
        $this->em->persist($customer);

        $product = new Product();
        $product
            ->setName($this->faker->domainName)
            ->setDescription($this->faker->paragraph)
            ->setFactor(0.55)
            ->setCreateDate($this->faker->dateTimeBetween('-123 days', '-1 days'))
        ;
        $this->em->persist($product);

        $agreements = [
            [
                'createDate' => \DateTime::createFromFormat('Y-m-d', '2020-01-10'), 'deleted' => 0, 'factorBindDate' => \DateTime::createFromFormat('Y-m-d', '2020-01-10'), 'factor' => 0.60,
                'production' => [
                    ['dpt' => 'dpt01', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-01-10')],
                    ['dpt' => 'dpt02', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-01-10')],
                    ['dpt' => 'dpt03', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-01-10')],
                    ['dpt' => 'dpt04', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-01-10')],
                    ['dpt' => 'dpt05', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-01-10')],
                ]
            ],
            [
                'createDate' => \DateTime::createFromFormat('Y-m-d', '2020-01-20'), 'deleted' => 0, 'factorBindDate' => \DateTime::createFromFormat('Y-m-d', '2020-02-11'), 'factor' => 0.30,
                'production' => [
                    ['dpt' => 'dpt01', 'status' => 2, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-01-20')],
                    ['dpt' => 'dpt02', 'status' => 2, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-01-20')],
                    ['dpt' => 'dpt03', 'status' => 2, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-01-20')],
                    ['dpt' => 'dpt04', 'status' => 2, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-01-20')],
                    ['dpt' => 'dpt05', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-01-20')],
                ]
            ],
            [
                'createDate' => \DateTime::createFromFormat('Y-m-d', '2020-02-10'), 'deleted' => 1, 'factorBindDate' => \DateTime::createFromFormat('Y-m-d', '2020-02-12'), 'factor' => 0.88,
                'production' => [
                    ['dpt' => 'dpt01', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-10')],
                    ['dpt' => 'dpt02', 'status' => 2, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-10')],
                    ['dpt' => 'dpt03', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-10')],
                    ['dpt' => 'dpt04', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-10')],
                    ['dpt' => 'dpt05', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-10')],
                ]
            ],
            [
                'createDate' => \DateTime::createFromFormat('Y-m-d', '2020-02-20'), 'deleted' => 0, 'factorBindDate' => \DateTime::createFromFormat('Y-m-d', '2020-02-13'), 'factor' => 0.12,
                'production' => [
                    ['dpt' => 'dpt01', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-20')],
                    ['dpt' => 'dpt02', 'status' => 2, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-20')],
                    ['dpt' => 'dpt03', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-20')],
                    ['dpt' => 'dpt04', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-20')],
                    ['dpt' => 'dpt05', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-20')],
                ]
            ],
            [
                'createDate' => \DateTime::createFromFormat('Y-m-d', '2020-02-21'), 'deleted' => 0, 'factorBindDate' => null, 'factor' => 0.24,
                'production' => [
                    ['dpt' => 'dpt01', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-21')],
                    ['dpt' => 'dpt02', 'status' => 2, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-21')],
                    ['dpt' => 'dpt03', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-21')],
                    ['dpt' => 'dpt04', 'status' => 1, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-21')],
                    ['dpt' => 'dpt05', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-21')],
                ]
            ],
            [
                'createDate' => \DateTime::createFromFormat('Y-m-d', '2020-02-22'), 'deleted' => 0, 'factorBindDate' => \DateTime::createFromFormat('Y-m-d', '2020-02-14'), 'factor' => 0.05,
                'production' => [
                    ['dpt' => 'dpt01', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-22')],
                    ['dpt' => 'dpt02', 'status' => 2, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-22')],
                    ['dpt' => 'dpt03', 'status' => 3, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-22')],
                    ['dpt' => 'dpt04', 'status' => 1, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-22')],
                    ['dpt' => 'dpt05', 'status' => 1, 'created' => \DateTime::createFromFormat('Y-m-d', '2020-02-22')],
                ]
            ],
        ];

        foreach ($agreements as $singleAgreement) {
            $agreement = new Agreement();
            $agreement
                ->setCreateDate($singleAgreement['createDate'])
                ->setUpdateDate($singleAgreement['createDate'])
                ->setCustomer($customer)
                ->setOrderNumber($this->faker->uuid)
                ->setStatus(0)
            ;
            $this->em->persist($agreement);

            $agreementLine = new AgreementLine();
            $agreementLine
                ->setProduct($product)
                ->setArchived(0)
                ->setDeleted($singleAgreement['deleted'])
                ->setDescription($this->faker->paragraph)
                ->setConfirmedDate($this->faker->dateTime)
                ->setFactorBindDate($singleAgreement['factorBindDate'])
                ->setFactor($singleAgreement['factor'])
                ->setAgreement($agreement)
            ;
            $this->em->persist($agreementLine);

            foreach ($singleAgreement['production'] as $p) {
                $production = new Production();
                $production->setAgreementLine($agreementLine)
                    ->setCreatedAt($p['created'])
                    ->setUpdatedAt($p['created'])
                    ->setDepartmentSlug($p['dpt'])
                    ->setStatus($p['status'])
                ;
                $this->em->persist($production);
            }
        }
        $this->em->flush();
    }
}