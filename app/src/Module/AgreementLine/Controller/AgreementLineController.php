<?php

namespace App\Module\AgreementLine\Controller;

use App\Controller\BaseController;
use App\Module\AgreementLine\Entity\AgreementLineRM;
use App\Module\AgreementLine\Repository\AgreementLineRMRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AgreementLineController extends BaseController
{
    #[Route(path: '/rm/{agreementLineRM}', methods: ['GET'])]
    public function fetchSingle(AgreementLineRM $agreementLineRM): Response
    {
        return $this->json(['data' => $agreementLineRM], Response::HTTP_OK);
    }

    #[Route(path: '/rm/search', methods: ['POST'])]
    public function search(
        Request $request,
        AgreementLineRMRepository $agreementLineRepository,
        PaginatorInterface $paginator,
    ): Response {
        $payload = $request->request->all();
        if (!isset($payload['search']) || !is_array($payload['search'])) {
            $payload['search'] = [];
        }
        $payload['search']['hasProduction'] = true;

        if ($this->isGranted('ROLE_CUSTOMER')) {
            $payload['search']['ownedBy'] = $this->getUser();
        }

        $query = $agreementLineRepository->search($payload);
        $query->setHydrationMode(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        $data = $paginator->paginate(
            $query,
            $payload['search']['page'] ?? 1,
            20
        );

        $paginationMeta = $data->getPaginationData();

        return $this->json([
            'data' => $data,
            'meta' => [
                'current' => $paginationMeta['current'],
                'pages' => $paginationMeta['pageCount'],
                'totalCount' => $paginationMeta['totalCount'],
                'pageSize' => $paginationMeta['numItemsPerPage']
            ]
        ], Response::HTTP_OK);
    }
}
