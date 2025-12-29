<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'app_dashboard')]
    public function index(SessionInterface $session): Response
    {
        if (!$session->get('admin_logged_in')) {
            return $this->redirectToRoute('app_admin_login');
        }

        return new Response('<h1>🍔 Brasil Burger - Dashboard</h1><p>Bienvenue dans votre espace admin !</p><a href="/admin/logout">Se déconnecter</a>');
    }
}