<?php

namespace App\Module\Agreement\Controller;

use App\Controller\BaseController;
use App\Module\Agreement\ReadModel\AgreementLineRM;
use App\Module\Agreement\Repository\AgreementLineRMRepository;
use App\Module\Agreement\Service\LegacyAgreementLineMapper;
use Doctrine\ORM\AbstractQuery;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/agreement-line')]
class AgreementLineRmController extends BaseController
{
    #[Route(path: '/rm/{agreementLineRM}', methods: ['GET'])]
    public function fetchSingle(AgreementLineRM $agreementLineRM): Response
    {
        return $this->json(['data' => $agreementLineRM], Response::HTTP_OK);
    }

    /**
     * Orders list, served from the read model and reshaped to the legacy
     * AgreementLine payload. Unlike /rm/search this does NOT force
     * hasProduction — the orders list shows every order, including those
     * without (or with only ghost) production.
     *
     * @IsGranted("ROLE_PRODUCTION_VIEW")
     */
    #[Route(path: '/rm/orders', options: ['expose' => true], methods: ['POST'])]
    public function searchForOrders(
        Request $request,
        AgreementLineRMRepository $agreementLineRepository,
        PaginatorInterface $paginator,
        LegacyAgreementLineMapper $mapper,
    ): Response {
        $payload = $request->request->all();
        if (!isset($payload['search']) || !is_array($payload['search'])) {
            $payload['search'] = [];
        }

        if ($this->isGranted('ROLE_CUSTOMER')) {
            $payload['search']['ownedBy'] = $this->getUser();
        }

        $query = $agreementLineRepository->search($payload);
        $query->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

        $data = $paginator->paginate(
            $query,
            $payload['search']['page'] ?? 1,
            20
        );

        $paginationMeta = $data->getPaginationData();

        return $this->json([
            'data' => array_map([$mapper, 'mapRow'], iterator_to_array($data)),
            'meta' => [
                'current' => $paginationMeta['current'],
                'pages' => $paginationMeta['pageCount'],
                'totalCount' => $paginationMeta['totalCount'],
                'pageSize' => $paginationMeta['numItemsPerPage'],
            ],
        ], Response::HTTP_OK);
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
