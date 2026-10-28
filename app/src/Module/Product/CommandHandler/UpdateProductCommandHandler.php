<?php

namespace App\Module\Product\CommandHandler;

use App\Module\Product\Command\UpdateProductCommand;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class UpdateProductCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductRepository $productRepository,
    ) {
    }

    public function __invoke(UpdateProductCommand $command): void
    {
        $product = $this->productRepository->find($command->productId);
        if ($product === null) {
            throw new \InvalidArgumentException('Produkt nie istnieje');
        }

        $product->setName($command->name);
        $product->setFactor($command->factor);
        $product->setDescription($command->description);

        $this->em->flush();
    }
}
