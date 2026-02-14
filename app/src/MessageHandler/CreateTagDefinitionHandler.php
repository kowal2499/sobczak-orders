<?php
/** @author: Roman Kowalski */

namespace App\MessageHandler;

use App\Entity\TagDefinition;
use App\Message\CreateTagDefinition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CreateTagDefinitionHandler implements MessageHandlerInterface
{
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function __invoke(CreateTagDefinition $createTagDefinition)
    {
        $dto = $createTagDefinition->getTagDefinitionDTO();
        $tagDefinition = new TagDefinition(
            $dto->getName(),
            $dto->getModule(),
            $dto->getIcon(),
            $dto->getColor()
        );

        $this->manager->persist($tagDefinition);
        $this->manager->flush();
    }
}