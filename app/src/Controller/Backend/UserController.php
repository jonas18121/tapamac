<?php

namespace App\Controller\Backend;

use App\Entity\User;
use App\Form\UserType;
use App\Manager\UserManager;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class UserController extends AbstractController
{
    #[Route(
        'backend/user', 
        name: 'app_backend_user_list'
    )]
    public function list(
        UserRepository $userRepository, 
        Request $request
    ): Response
    {
        return $this->render('backend/user/list.html.twig', [
            'pagination' => $userRepository->findPaginationList($request->query->getInt('page', 1), 'user', 1)
        ]);
    }

    #[Route(
        'backend/user/create', 
        name: 'app_backend_user_create'
    )]
    public function create(
        Request $request,
        UserManager $userManager
    ): Response
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_home_page');
        }

        $userCreate = new User();
        $form = $this->createForm(
            UserType::class, 
            $userCreate,
            [
                'window_user' => 'backend',
                'use_password' => 'use_password'
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userManager->create($userCreate);

            // add flash
            $this->addFlash(
                'success',
                'Le produit a bien été créée'
            );

            // Redirection
            return $this->redirectToRoute('app_backend_user_list');
        }

        return $this->render('backend/user/create.html.twig', [
            'formUser' => $form->createView(),
        ]);
    }

    #[Route(
        'backend/user/delete/{id}', 
        name: 'app_backend_user_delete', 
        requirements: ["id" => "\d+"],
        methods: ["DELETE"]
    )]
    public function delete(
        User $userDelete,
        UserManager $userManager,
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

        // $this->denyAccessUnlessGranted('delete', $user);

        if ($this->isCsrfTokenValid('delete', $token)) {
            $userManager->delete($userDelete);
        }

        // add flash
        $this->addFlash(
            'success',
            'Le produit a bien été supprimée'
        );

         // Redirection
        return $this->redirectToRoute('app_backend_user_list');
    }

    #[Route(
        'backend/user/update/{id}', 
        name: 'app_backend_user_update', 
        requirements: ["id" => "\d+"],
        methods: ["GET", "PUT"]
    )]
    public function update(
        User $userUpdate,
        Request $request,
        UserManager $userManager
    ): Response 
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_home_page');
        }

        // voter
        // $this->denyAccessUnlessGranted('edit', $storageSpace);

        $form = $this->createForm(
            UserType::class, 
            $userUpdate, 
            [
                'method' => 'PUT',
                'window_user' => 'backend',
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userManager->update($userUpdate);

            // add flash
            $this->addFlash(
                'success',
                'Le produit a bien été modifié'
            );

            // Redirection
            return $this->redirectToRoute('app_backend_user_list');
        }

        return $this->render('backend/user/update.html.twig', [
            'formUser' => $form->createView(),
        ]);
    }

    #[Route(
        'backend/user/{id}', 
        name: 'app_backend_user_detail', 
        requirements: ["id" => "\d+"],
        methods: ["GET"]
    )]
    public function detail(
        User $userDetail
    ): Response
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_home_page');
        }

        // $this->denyAccessUnlessGranted('show', $user);

        return $this->render('backend/user/detail.html.twig', [
            'user' => $userDetail,
        ]);
    }
}
