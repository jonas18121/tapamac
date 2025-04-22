<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('backend/user', name: 'app_backend_user')]
    public function index(): Response
    {
        return $this->render('backend/pages/user/list.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }
}
