<?php

namespace App\Module\Product\CommandHandler;

use App\Entity\Product;
use App\Module\Product\Command\CreateProductCommand;
use Doctrine\ORM\EntityManagerInterface;

class CreateProductCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(CreateProductCommand $command): void
    {
        $product = new Product();
        $product->setName($command->name);
        $product->setFactor($command->factor);
        $product->setDescription($command->description);
        $product->setCreateDate(new \DateTime());

        $this->em->persist($product);
        $this->em->flush();
    }
}
