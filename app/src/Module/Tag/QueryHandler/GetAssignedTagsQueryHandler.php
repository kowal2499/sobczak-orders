<?php
/** @author: Roman Kowalski */

namespace App\Module\Tag\QueryHandler;

use App\Module\Tag\Query\GetAssignedTagsQuery;
use App\Module\Tag\Repository\TagAssignmentRepository;

class GetAssignedTagsQueryHandler
{
    public function __construct(
        private readonly TagAssignmentRepository $tagAssignmentRepository
    ) {
    }

    public function __invoke(GetAssignedTagsQuery $query): array
    {
        return $this->tagAssignmentRepository->findAllInModule(
            $query->getContextId(),
            $query->getModule()
        );
    }
}
