<?php

namespace App\Controller\Backend;

use App\Entity\User;
use App\DTO\SearchData;
use App\Entity\Product;
use App\Form\SearchType;
use App\Form\ProductType;
use App\Manager\ProductManager;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ProductController extends AbstractController
{
    #[Route(
        'backend/product', 
        name: 'app_backend_product_list'
    )]
    public function list(
        ProductManager $productManager,
        Request $request
    ): Response
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_home_page');
        }
        
        $searchData = new SearchData();
        $formSearch = $this->createForm(SearchType::class, $searchData);

        $formSearch->handleRequest($request);

        if($formSearch->isSubmitted() && $formSearch->isValid()){
            $searchData->setPage($request->query->getInt('page', 1));
            $pagination = $productManager->search($searchData);
        }
        else {
            // Tous les produits
            $pagination = $productManager->list($request->query->getInt('page', 1), 'product', 2);
        }

        return $this->render('backend/product/backend_product_list.html.twig', [
            'pagination' => $pagination,
            'formSearch' => $formSearch->createView()
        ]);
    }

    #[Route(
        'backend/product/create', 
        name: 'app_backend_product_create'
    )]
    public function create(
        Request $request,
        ProductManager $productManager
    ): Response
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_home_page');
        }

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            /** @var Array $files */
            $files = $form->get('uploadImages')->getData();
            $productManager->createOrUpdateWithUploadImage(
                $product, 
                $files, 
                '/public/uploads/images/products', 
                'create'
            );

            // add flash
            $this->addFlash(
                'success',
                'Le produit a bien été créée'
            );

            // Redirection
            return $this->redirectToRoute('app_backend_product_list');
        }

        return $this->render('backend/product/backend_product_create.html.twig', [
            'formProduct' => $form->createView(),
        ]);
    }

    #[Route(
        'backend/product/delete/{id}', 
        name: 'app_backend_product_delete', 
        requirements: ["id" => "\d+"],
        methods: ["DELETE"]
    )]
    public function delete(
        Product $product,
        ProductManager $productManager,
        Request $request
    ): Response 
    {
        /** @var User|null */
        $user = $this->getUser();

        /** @var string|null */
        $token = $request->get('_token');

        if (!$user || null === $token) {
            return $this->redirectToRoute('app_home_page');
        }

        // $this->denyAccessUnlessGranted('delete', $product);

        if ($this->isCsrfTokenValid('delete', $token)) {
            $productManager->delete($product);
        }

        // add flash
        $this->addFlash(
            'success',
            'Le produit a bien été supprimée'
        );

         // Redirection
        return $this->redirectToRoute('app_backend_product_list');
    }

    #[Route(
        'backend/product/update/{id}', 
        name: 'app_backend_product_update', 
        requirements: ["id" => "\d+"],
        methods: ["GET", "PUT"]
    )]
    public function update(
        Product $product,
        Request $request,
        ProductManager $productManager
    ): Response 
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_home_page');
        }

        // voter
        // $this->denyAccessUnlessGranted('edit', $storageSpace);

        $form = $this->createForm(ProductType::class, $product, ['method' => 'PUT']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            /** @var Array $files */
            $files = $form->get('uploadImages')->getData();
            $productManager->createOrUpdateWithUploadImage(
                $product, 
                $files, 
                '/public/uploads/images/products', 
                'update'
            );

            // add flash
            $this->addFlash(
                'success',
                'Le produit a bien été modifié'
            );

            // Redirection
            return $this->redirectToRoute('app_backend_product_list');
        }

        return $this->render('backend/product/backend_product_update.html.twig', [
            'formProduct' => $form->createView(),
        ]);
    }

    #[Route(
        'backend/product/{id}', 
        name: 'app_backend_product_detail', 
        requirements: ["id" => "\d+"],
        methods: ["GET"]
    )]
    public function detail(
        Product $product
    ): Response
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_home_page');
        }

        // $this->denyAccessUnlessGranted('show', $product);

        return $this->render('backend/product/backend_product_detail.html.twig', [
            'product' => $product,
        ]);
    }
}
