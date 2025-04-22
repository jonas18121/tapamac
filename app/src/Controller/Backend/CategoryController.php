<?php

namespace App\Controller\Backend;

use App\Entity\User;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Manager\CategoryManager;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CategoryController extends AbstractController
{
    #[Route('backend/category', name: 'app_backend_category_list')]
    public function list(CategoryRepository $categoryRepository, Request $request): Response
    {
        return $this->render('backend/pages/category/list.html.twig', [
            'pagination' => $categoryRepository->findListCategories($request->query->getInt('page', 1))
        ]);
    }

    #[Route('backend/category/create', name: 'app_backend_category_create')]
    public function create(
        Request $request,
        CategoryManager $categoryManager
    ): Response
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_home_page');
        }

        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categoryManager->create($category, $user);

            // add flash
            $this->addFlash(
                'success',
                'La catégorie a bien été créée'
            );

            // Redirection
            return $this->redirectToRoute('app_backend_category_list');
        }

        return $this->render('backend/pages/category/create.html.twig', [
            'formCategory' => $form->createView(),
        ]);
    }

    #[Route(
        'backend/category/delete/{id}', 
        name: 'app_backend_category_delete', 
        requirements: ["id" => "\d+"],
        methods: ["DELETE"]
    )]
    public function delete(
        Category $category,
        CategoryManager $categoryManager,
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

        // $this->denyAccessUnlessGranted('delete', $category);

        if ($this->isCsrfTokenValid('delete', $token)) {
            $categoryManager->delete($category);
        }

        // add flash
        $this->addFlash(
            'success',
            'La catégorie a bien été supprimée'
        );

         // Redirection
        return $this->redirectToRoute('app_backend_category_list');
    }


    #[Route(
        'backend/category/update/{id}', 
        name: 'app_backend_category_update', 
        requirements: ["id" => "\d+"],
        methods: ["GET", "PUT"]
    )]
    public function update(
        Category $category,
        Request $request,
        CategoryManager $categoryManager
    ): Response 
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_home_page');
        }

        // voter
        // $this->denyAccessUnlessGranted('edit', $storageSpace);

        $form = $this->createForm(CategoryType::class, $category, ['method' => 'PUT']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $categoryManager->update($category);

            // add flash
            $this->addFlash(
                'success',
                'La catégorie a bien été modifié'
            );

            // Redirection
            return $this->redirectToRoute('app_backend_category_list');
        }

        return $this->render('backend/pages/category/update.html.twig', [
            'formCategory' => $form->createView(),
        ]);
    }

    #[Route(
        'backend/category/{id}', 
        name: 'app_backend_category_detail', 
        requirements: ["id" => "\d+"],
        methods: ["GET"]
    )]
    public function detail(Category $category): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_home_page');
        }

        // $this->denyAccessUnlessGranted('show', $category);

        return $this->render('backend/pages/category/detail.html.twig', [
            'category' => $category,
        ]);
    }
}
