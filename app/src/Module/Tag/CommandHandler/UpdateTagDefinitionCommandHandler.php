<?php
/** @author: Roman Kowalski */

namespace App\Module\Tag\CommandHandler;

use App\Module\Tag\Command\UpdateTagDefinitionCommand;
use App\Module\Tag\Repository\TagDefinitionRepository;
use App\Utilities\Slugger;
use Doctrine\ORM\EntityManagerInterface;

class UpdateTagDefinitionCommandHandler
{
    public function __construct(
        private readonly TagDefinitionRepository $repository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    public function __invoke(UpdateTagDefinitionCommand $command): void
    {
        $tagDefinition = $this->repository->find($command->getId());

        if (null === $tagDefinition) {
            throw new \InvalidArgumentException('Given tag definition does not exist.');
        }

        $dto = $command->getTagDefinitionDTO();
        $tagDefinition->setName($dto->getName());
        $tagDefinition->setModule($dto->getModule());
        $tagDefinition->setColor($dto->getColor());
        $tagDefinition->setIcon($dto->getIcon());
        $tagDefinition->setSlug($dto->getSlug() ?? Slugger::slugify($dto->getName()));
        $this->manager->flush();
    }
}
