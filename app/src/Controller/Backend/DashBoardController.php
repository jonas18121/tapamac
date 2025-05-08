<?php

namespace App\Controller\Backend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashBoardController extends AbstractController
{
    #[Route('backend/dashboard', name: 'app_backend_dashboard')]
    public function index(): Response
    {
        return $this->render('backend/bashboard/index.html.twig', [
            'controller_name' => 'bashboard',
        ]);
    }
}
