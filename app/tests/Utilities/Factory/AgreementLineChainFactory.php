<?php

namespace App\Tests\Utilities\Factory;

use App\Entity\Agreement;
use App\Entity\AgreementLine;
use App\Entity\Customer;
use App\Entity\Product;

class AgreementLineChainFactory
{
    private $factory;

    public function __construct(EntityFactory $factory)
    {
        $this->factory = $factory;
    }

    public function make(
        $agreementParams = [],
        $agreementLineParams = []
    ): AgreementLine
    {
        $customer = $this->factory->make(Customer::class);
        $product = $this->factory->make(Product::class);
        $agreement = $this->factory->make(Agreement::class, array_merge([
                'customer' => $customer
            ], $agreementParams)
        );
        $agreementLine = $this->factory->make(
            AgreementLine::class, array_merge([
                'product' => $product,
                'agreement' => $agreement
            ], $agreementLineParams)
        );

        $this->factory->flush();

        return $agreementLine;
    }
}