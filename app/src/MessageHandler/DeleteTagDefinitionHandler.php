<?php
/** @author: Roman Kowalski */

namespace App\MessageHandler;


use App\Entity\TagDefinition;
use App\Message\DeleteTagDefinition;
use App\Repository\TagDefinitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class DeleteTagDefinitionHandler implements MessageHandlerInterface
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
    public function __invoke(DeleteTagDefinition $deleteTagDefinition)
    {
        /** @var TagDefinition|null $tagDefinition */
        $tagDefinition = $this->repository->find($deleteTagDefinition->getId());

        if (null === $tagDefinition) {
            throw new \Exception('Given tag definition not exists.');
        }

        $tagDefinition->setIsDeleted(true);
        $this->manager->flush();
    }
}