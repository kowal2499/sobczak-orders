<?php
/** @author: Roman Kowalski */

namespace App\Module\Tag\CommandHandler;

use App\Module\Tag\Command\DeleteTagDefinitionCommand;
use App\Module\Tag\Repository\TagDefinitionRepository;
use Doctrine\ORM\EntityManagerInterface;

class DeleteTagDefinitionCommandHandler
{
    public function __construct(
        private readonly TagDefinitionRepository $repository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    public function __invoke(DeleteTagDefinitionCommand $command): void
    {
        $tagDefinition = $this->repository->find($command->getId());

        if (null === $tagDefinition) {
            throw new \InvalidArgumentException('Given tag definition does not exist.');
        }

        $tagDefinition->setIsDeleted(true);
        $this->manager->flush();
    }
}
