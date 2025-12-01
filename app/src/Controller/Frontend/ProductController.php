<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Entity\Product;
use App\Form\ProductType;
use App\Manager\ProductManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ProductController extends AbstractController
{
    #[Route(
        'product', 
        name: 'app_product_list'
    )]
    public function list(
        ProductManager $productManager,
        Request $request
    ): Response
    {
        throw new \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException('Bearer', "Test erreur ERROR : non authentifié !"); 
        return $this->render('frontend/product/product_list.html.twig', [
            'pagination' => $productManager->list($request->query->getInt('page', 1), 'product', 15)
        ]);
    }

    #[Route(
        'product/create', 
        name: 'app_product_create'
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
            return $this->redirectToRoute('app_product_list');
        }

        return $this->render('frontend/product/product_create.html.twig', [
            'formProduct' => $form->createView(),
        ]);
    }

    #[Route(
        'product/delete/{id}', 
        name: 'app_product_delete', 
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
        return $this->redirectToRoute('app_product_list');
    }

    #[Route(
        'product/update/{id}', 
        name: 'app_product_update', 
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
        // $this->denyAccessUnlessGranted('edit', $product);

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
            return $this->redirectToRoute('app_product_list');
        }

        return $this->render('frontend/product/product_update.html.twig', [
            'formProduct' => $form->createView(),
        ]);
    }

    #[Route(
        'product/{id}', 
        name: 'app_product_detail', 
        requirements: ["id" => "\d+"],
        methods: ["GET"]
    )]
    public function detail(
        Product $product
    ): Response
    {
        return $this->render('frontend/product/product_detail.html.twig', [
            'product' => $product,
        ]);
    }
}
