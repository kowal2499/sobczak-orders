<?php
/** @author: Roman Kowalski */

namespace App\Module\Tag\CommandHandler;

use App\Module\Tag\Command\AssignTagsCommand;
use App\Module\Tag\Entity\TagAssignment;
use App\Module\Tag\Repository\TagAssignmentRepository;
use App\Module\Tag\Repository\TagDefinitionRepository;
use App\Repository\AgreementLineRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class AssignTagsCommandHandler
{
    public function __construct(
        private readonly TagAssignmentRepository $tagAssignmentRepository,
        private readonly TagDefinitionRepository $tagDefinitionRepository,
        private readonly AgreementLineRepository $agreementLineRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    public function __invoke(AssignTagsCommand $command): void
    {
        $tagsToAssign = $command->getTags();

        /** @var TagAssignment[] $existingAssignments */
        $existingAssignments = $this->tagAssignmentRepository->findAllInModule(
            $command->getContextId(),
            $command->getModule()
        );

        // remove tags which were not passed with command
        foreach ($existingAssignments as $existing) {
            $pos = array_search($existing->getTagDefinition()->getId(), $tagsToAssign);
            if ($pos === false) {
                $this->manager->remove($existing);
            } else {
                // skip existing tags
                unset($tagsToAssign[$pos]);
            }
        }

        $agreementLine = $this->agreementLineRepository->find($command->getContextId());

        foreach ($this->tagDefinitionRepository->findAllById(array_values($tagsToAssign)) as $tag) {
            $tagAssignment = new TagAssignment();
            $tagAssignment->setContextId($agreementLine);
            $tagAssignment->setTagDefinition($tag);
            $tagAssignment->setUser($this->userRepository->find($command->getUserId()));
            $this->manager->persist($tagAssignment);
        }

        $this->manager->flush();
    }
}
