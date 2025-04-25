<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Form\UserType;
use App\Manager\UserManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class UserAccountController extends AbstractController
{
    #[Route(
        'user/delete/{id}', 
        name: 'app_user_delete', 
        requirements: ["id" => "\d+"],
        methods: ["DELETE"]
    )]
    public function delete(
        User $userDelete,
        UserManager $userManager,
        Request $request,
        Security $security
    ): Response 
    {
        /** @var User|null */
        $user = $this->getUser();

        /** @var string|null */
        $token = $request->get('_token');

        if (!$user || null === $token) {
            return $this->redirectToRoute('app_home_page');
        }

        // Déconnexion de l'utilisateur
        $security->logout(false);

        // $this->denyAccessUnlessGranted('delete', $user);

        // Suppression du compte utilisateur
        if ($this->isCsrfTokenValid('delete', $token)) {
            $userManager->delete($userDelete);
        }

        // add flash
        $this->addFlash(
            'success',
            'Votre compte a bien été supprimée'
        );

         // Redirection
        return $this->redirectToRoute('app_home_page');
    }

    #[Route(
        'user/update/{id}', 
        name: 'app_user_update', 
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

        $form = $this->createForm(UserType::class, $userUpdate, ['method' => 'PUT']);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userManager->update($userUpdate);

            // add flash
            $this->addFlash(
                'success',
                'Votre compte a bien été modifié'
            );

            // Redirection
            return $this->redirectToRoute('app_user_detail', ['id' => $user->getId()]);
        }

        return $this->render('frontend/pages/user/update.html.twig', [
            'formUser' => $form->createView(),
        ]);
    }

    #[Route(
        'user/{id}', 
        name: 'app_user_detail', 
        requirements: ["id" => "\d+"],
        methods: ["GET"]
    )]
    public function detail(
    ): Response
    {
        /** @var User|null */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_home_page');
        }

        // $this->denyAccessUnlessGranted('show', $user);

        return $this->render('frontend/pages/user/detail.html.twig', [
            'user' => $user,
        ]);
    }
}
