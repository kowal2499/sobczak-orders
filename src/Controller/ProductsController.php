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

class ProductsController extends AbstractController
{
    /**
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
     * @Route("/products/new", name="products_new")
     * @param EntityManagerInterface $em
     * @return Response
     * @throws \Exception
     */
    public function addNew(Request $request, EntityManagerInterface $em)
    {

        $product = new Product();

        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product = $form->getData();
            $product->setCreateDate(new \DateTime('now'));
            $em->persist($product);
            $em->flush();

            $this->addFlash('success', 'Dodano nowy produkt.');
            return $this->redirectToRoute('products_show');
        }

        return $this->render('products/product_single.html.twig', [
            'form' => $form->createView(),
            'title' => 'Nowy produkt',
        ]);
    }

    /**
     * @Route("/products/edit/{id}", name="products_edit", options={"expose"=true})
     * @param Product $product
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function edit(Product $product, Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request, $product);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Zmiany zostały zapisane.');
            return $this->redirectToRoute('products_show');
        }

        return $this->render('products/product_single.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edytuj produkt'
        ]);
    }

    /**
     * @Route("/products/fetch", name="products_fetch", options={"expose"=true})
     * @param Request $request
     */
    public function fetch(Request $request, ProductRepository $repository)
    {
        return new JsonResponse($repository->getByName()->getArrayResult());
    }
}
