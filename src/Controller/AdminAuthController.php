<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminAuthController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    public function redirectToLogin(): Response
    {
        return $this->redirectToRoute('app_admin_login');
    }
}