<?php

/** @author: Roman Kowalski */

namespace App\Module\Tag\CommandHandler;

use App\Module\Tag\Command\CreateTagDefinitionCommand;
use App\Module\Tag\Entity\TagDefinition;
use App\Utilities\Slugger;
use Doctrine\ORM\EntityManagerInterface;

class CreateTagDefinitionCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $manager
    ) {
    }

    public function __invoke(CreateTagDefinitionCommand $command): void
    {
        $dto = $command->getTagDefinitionDTO();
        $tagDefinition = new TagDefinition(
            $dto->getName(),
            $dto->getModule(),
            $dto->getIcon(),
            $dto->getColor(),
            $dto->getSlug() ?? Slugger::slugify($dto->getName())
        );

        $this->manager->persist($tagDefinition);
        $this->manager->flush();
    }
}
