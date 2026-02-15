<?php
/** @author: Roman Kowalski */

namespace App\MessageHandler;

use App\Entity\TagAssignment;
use App\Message\AssignTags;
use App\Repository\AgreementLineRepository;
use App\Repository\TagAssignmentRepository;
use App\Repository\TagDefinitionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class AssignTagsHandler implements MessageHandlerInterface
{
    private $tagAssignmentRepository;
    private $tagDefinitionRepository;
    private $agreementLineRepository;
    private $manager;
    private $userRepository;

    /**
     * AssignTagsHandler constructor.
     * @param TagAssignmentRepository $tagAssignmentRepository
     * @param TagDefinitionRepository $tagDefinitionRepository
     * @param AgreementLineRepository $agreementLineRepository
     * @param UserRepository $userRepository
     * @param EntityManagerInterface $manager
     */
    public function __construct(
        TagAssignmentRepository $tagAssignmentRepository,
        TagDefinitionRepository $tagDefinitionRepository,
        AgreementLineRepository $agreementLineRepository,
        UserRepository $userRepository,
        EntityManagerInterface $manager)
    {
        $this->tagAssignmentRepository = $tagAssignmentRepository;
        $this->tagDefinitionRepository = $tagDefinitionRepository;
        $this->userRepository = $userRepository;
        $this->agreementLineRepository = $agreementLineRepository;
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

        $agreementLine = $this->agreementLineRepository->find($assignTags->getContextId());

        foreach($this->tagDefinitionRepository->findAllById(array_values($tagsToAssign)) as $tag) {
            $tagAssignment = new TagAssignment();
            $tagAssignment->setContextId($agreementLine);
            $tagAssignment->setTagDefinition($tag);
            $tagAssignment->setUser($this->userRepository->find($assignTags->getUserId()));
            $this->manager->persist($tagAssignment);
        }
        $this->manager->flush();
    }
}