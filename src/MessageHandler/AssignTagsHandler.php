<?php
/** @author: Roman Kowalski */

namespace App\MessageHandler;

use App\Entity\TagAssignment;
use App\Message\AssignTags;
use App\Repository\TagAssignmentRepository;
use App\Repository\TagDefinitionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class AssignTagsHandler implements MessageHandlerInterface
{
    private $tagAssignmentRepository;
    private $tagDefinitionRepository;
    private $manager;
    private $userRepository;

    public function __construct(
        TagAssignmentRepository $tagAssignmentRepository,
        TagDefinitionRepository $tagDefinitionRepository,
        UserRepository $userRepository,
        EntityManagerInterface $manager)
    {
        $this->tagAssignmentRepository = $tagAssignmentRepository;
        $this->tagDefinitionRepository = $tagDefinitionRepository;
        $this->userRepository = $userRepository;
        $this->manager = $manager;
    }

    public function __invoke(AssignTags $assignTags)
    {
        $tagsToAssign = $assignTags->getTags();

        /** @var TagAssignment[] $existingAssignments */
        $existingAssignments = $this->tagAssignmentRepository->findAllInModule(
            $assignTags->getContextId(), $assignTags->getModule());

        // remove tags which were not passed with message
        foreach ($existingAssignments as $existing) {
            $pos = array_search($existing->getTagDefinition()->getId(), $tagsToAssign);
            if ($pos === false) {
                $this->manager->remove($existing);
            } else {
                // skip existing tags
                unset($tagsToAssign[$pos]);
            }
        }

        foreach($this->tagDefinitionRepository->findAllById(array_values($tagsToAssign)) as $tag) {
            $tagAssignment = new TagAssignment();
            $tagAssignment->setContextId($assignTags->getContextId());
            $tagAssignment->setTagDefinition($tag);
            $tagAssignment->setUser($this->userRepository->find($assignTags->getUserId()));
            $this->manager->persist($tagAssignment);
        }
        $this->manager->flush();
    }
}