<?php
/** @author: Roman Kowalski */

namespace App\MessageHandler;

use App\Entity\TagDefinition;
use App\Message\UpdateTagDefinition;
use App\Repository\TagDefinitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UpdateTagDefinitionHandler implements MessageHandlerInterface
{
    private $repository;
    private $manager;

    /**
     * UpdateTagDefinitionHandler constructor.
     * @param TagDefinitionRepository $repository
     * @param EntityManagerInterface $manager
     */
    public function __construct(TagDefinitionRepository $repository, EntityManagerInterface $manager)
    {
        $this->repository = $repository;
        $this->manager = $manager;
    }

    public function __invoke(UpdateTagDefinition $updateTagDefinition)
    {
        /** @var TagDefinition|null $tagDefinition */
        $tagDefinition = $this->repository->find($updateTagDefinition->getId());
        $dto = $updateTagDefinition->getTagDefinitionDTO();

        if (null === $tagDefinition) {
            throw new \Exception('Given tag definition not exists.');
        }
        $tagDefinition->setName($dto->getName());
        $tagDefinition->setModule($dto->getModule());
        $tagDefinition->setColor(($dto->getColor()));
        $tagDefinition->setIcon(($dto->getIcon()));
        $this->manager->flush();
    }
}