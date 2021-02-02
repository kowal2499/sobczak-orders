<?php
/** @author: Roman Kowalski */

namespace App\MessageHandler;

use App\Message\GetAssignedTags;
use App\Repository\TagAssignmentRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class GetAssignedTagsHandler implements MessageHandlerInterface
{
    private $tagAssignmentRepository;

    public function __construct(TagAssignmentRepository $tagAssignmentRepository)
    {
        $this->tagAssignmentRepository = $tagAssignmentRepository;
    }

    public function __invoke(GetAssignedTags $command)
    {
        dd($this->tagAssignmentRepository->findAllInModule(
            $command->getContextId(),
            $command->getModule()
        ));
    }
}