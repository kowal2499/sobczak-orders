<?php
/** @author: Roman Kowalski */

namespace App\Controller;

use App\DTO\TagDefinitionDTO;
use App\Entity\TagDefinition;
use App\Form\TagDefinitionDTOType;
use App\Message\CreateTagDefinition;
use App\Message\DeleteTagDefinition;
use App\Message\UpdateTagDefinition;
use App\Repository\TagDefinitionRepository;
use http\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class TagDefinitionController extends BaseController
{
    /**
     * @Route("/tag-definition", name="tags", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('configuration/tags.html.twig');
    }

    /**
     * @Route("/api/tag-definition", methods={"GET"})
     * @param TagDefinitionRepository $repository
     * @return JsonResponse
     */
    public function findAll(TagDefinitionRepository $repository): JsonResponse
    {
        return $this->json(
            $repository->notDeleted()->getResult(),
            Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => ['_main']]
        );
    }

    /**
     * @Route("/api/tag-definition/search", methods={"GET"})
     * @param Request $request
     * @param TagDefinitionRepository $repository
     * @return JsonResponse
     * @throws \Exception
     */
    public function search(Request $request, TagDefinitionRepository $repository): JsonResponse
    {
        $module = $request->query->getAlpha('module');
        if (!$module) {
            throw new \Exception('Module name is required.');
        }
        return $this->json($repository
            ->findByModule($module)
            ->getResult(), Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => ['_main']]
        );
    }

    /**
     * @Route("/api/tag-definition", methods={"POST"})
     * @param Request $request
     * @param MessageBusInterface $messageBus
     * @return JsonResponse
     */
    public function create(Request $request, MessageBusInterface $messageBus): JsonResponse
    {
        $form = $this->createForm(TagDefinitionDTOType::class);
        $this->processForm($request, $form);
        /** @var TagDefinitionDTO $dto */
        $dto = $form->getData();

        $messageBus->dispatch(new CreateTagDefinition($dto));
        return $this->json([], Response::HTTP_CREATED);
    }

    /**
     * @Route("/api/tag-definition/{tagDefinition}", methods={"PUT"})
     * @param TagDefinition $tagDefinition
     * @param Request $request
     * @param MessageBusInterface $messageBus
     * @return JsonResponse
     */
    public function update(TagDefinition $tagDefinition, Request $request, MessageBusInterface $messageBus): JsonResponse
    {
        $form = $this->createForm(TagDefinitionDTOType::class);
        $this->processForm($request, $form);
        /** @var TagDefinitionDTO $dto */
        $dto = $form->getData();

        $messageBus->dispatch(new UpdateTagDefinition($tagDefinition->getId(), $dto));
        return $this->json([], Response::HTTP_OK);
    }

    /**
     * @Route("/api/tag-definition/{tagDefinition}", methods={"DELETE"})
     * @param TagDefinition $tagDefinition
     * @param Request $request
     * @param MessageBusInterface $messageBus
     * @return JsonResponse
     */
    public function delete(TagDefinition $tagDefinition, MessageBusInterface $messageBus): JsonResponse
    {
        $messageBus->dispatch(new DeleteTagDefinition($tagDefinition->getId()));
        return $this->json([]);
    }
}