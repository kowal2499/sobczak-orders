<?php
/** @author: Roman Kowalski */

namespace App\Module\Tag\Controller;

use App\Controller\BaseController;
use App\Module\Tag\Command\CreateTagDefinitionCommand;
use App\Module\Tag\Command\DeleteTagDefinitionCommand;
use App\Module\Tag\Command\UpdateTagDefinitionCommand;
use App\Module\Tag\Entity\TagDefinition;
use App\Module\Tag\Form\TagDefinitionDTOType;
use App\Module\Tag\Repository\TagDefinitionRepository;
use App\System\CommandBus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class TagDefinitionController extends BaseController
{
    #[Route(path: '/tag-definition', name: 'tags', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('configuration/tags.html.twig');
    }

    #[Route(path: '/api/tag-definition', methods: ['GET'])]
    public function findAll(TagDefinitionRepository $repository): JsonResponse
    {
        return $this->json(
            $repository->notDeleted()->getResult(),
            Response::HTTP_OK,
            [],
            [AbstractNormalizer::GROUPS => ['_main']]
        );
    }

    #[Route(path: '/api/tag-definition/search', methods: ['GET'])]
    public function search(Request $request, TagDefinitionRepository $repository): JsonResponse
    {
        $module = $request->query->getAlpha('module');
        if (!$module) {
            throw new \Exception('Module name is required.');
        }

        return $this->json(
            $repository->findByModule($module)->getResult(),
            Response::HTTP_OK,
            [],
            [AbstractNormalizer::GROUPS => ['_main']]
        );
    }

    #[Route(path: '/api/tag-definition', methods: ['POST'])]
    #[IsGranted('tags.manage')]
    public function create(Request $request, CommandBus $commandBus): JsonResponse
    {
        $form = $this->createForm(TagDefinitionDTOType::class);
        $this->processForm($request, $form);

        $commandBus->dispatch(new CreateTagDefinitionCommand($form->getData()));

        return $this->json([], Response::HTTP_CREATED);
    }

    #[Route(path: '/api/tag-definition/{tagDefinition}', methods: ['PUT'])]
    #[IsGranted('tags.manage')]
    public function update(
        TagDefinition $tagDefinition,
        Request $request,
        CommandBus $commandBus
    ): JsonResponse {
        $form = $this->createForm(TagDefinitionDTOType::class);
        $this->processForm($request, $form);

        $commandBus->dispatch(new UpdateTagDefinitionCommand($tagDefinition->getId(), $form->getData()));

        return $this->json([], Response::HTTP_OK);
    }

    #[Route(path: '/api/tag-definition/{tagDefinition}', methods: ['DELETE'])]
    #[IsGranted('tags.manage')]
    public function delete(TagDefinition $tagDefinition, CommandBus $commandBus): JsonResponse
    {
        $commandBus->dispatch(new DeleteTagDefinitionCommand($tagDefinition->getId()));

        return $this->json([]);
    }
}
