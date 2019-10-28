<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductsController extends AbstractController
{
    /**
     * @isGranted("ROLE_PRODUCTS")
     *
     * @Route("/products", name="products_show")
     * @param Request $request
     * @param ProductRepository $repository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function products(Request $request, ProductRepository $repository, PaginatorInterface $paginator)
    {
        $products = $repository->getWithSearch($request->query->get('q'));

        $pagination = $paginator->paginate(
            $products,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('products/products_show.html.twig', [
            'products' => $pagination,
            'search' => $request->query->get('q')
        ]);
    }

    /**
     * @isGranted("ROLE_PRODUCTS")
     *
     * @Route("/products/new", name="products_new")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @return Response
     * @throws \Exception
     */
    public function addNew(Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
    {

        $product = new Product();

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = $form->getData();
            $product->setCreateDate(new \DateTime('now'));
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', $translator->trans('Dodano nowy produkt.', [], 'products'));
            return $this->redirectToRoute('products_show');
        }

        return $this->render('products/product_single.html.twig', [
            'form' => $form->createView(),
            'title' => $translator->trans('Nowy produkt', [], 'products'),
        ]);
    }

    /**
     * @isGranted("ROLE_PRODUCTS")
     *
     * @Route("/products/edit/{id}", name="products_edit", options={"expose"=true})
     * @param Product $product
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function edit(Product $product, Request $request, EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request, $product);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', $translator->trans('Zmiany zostały zapisane.', [], 'products'));
            return $this->redirectToRoute('products_show');
        }

        return $this->render('products/product_single.html.twig', [
            'form' => $form->createView(),
            'title' => $translator->trans('Edytuj produkt', [], 'products')
        ]);
    }

    /**
     * @Route("/products/fetch", name="products_fetch", options={"expose"=true})
     * @param Request $request
     * @param ProductRepository $repository
     * @return JsonResponse
     */
    public function fetch(Request $request, ProductRepository $repository)
    {
        $response = [
            'products' => $repository->getByName()->getArrayResult(),
        ];
        return new JsonResponse($response);
    }
}
