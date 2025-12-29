<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

class AdminAuthController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    public function redirectToLogin(): Response
    {
        return $this->redirectToRoute('app_admin_login');
    }

    #[Route('/admin/login', name: 'app_admin_login', methods: ['GET', 'POST'])]
    public function login(Request $request, Connection $connection, SessionInterface $session): Response
    {
        if ($session->get('admin_logged_in')) {
            return $this->redirectToRoute('app_dashboard');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            $user = $connection->fetchAssociative(
                'SELECT * FROM users WHERE email = ? AND type_user = ?',
                [$email, 'ADMIN']
            );

            if ($user && $email === 'admin@test.com' && $password === '123456') {
                $session->set('admin_logged_in', true);
                $session->set('admin_user', $user);
                return $this->redirectToRoute('app_dashboard');
            }
            
            $error = 'Email ou mot de passe incorrect';
        }

        return $this->render('admin/login.html.twig', ['error' => $error]);
    }

    #[Route('/admin/logout', name: 'app_admin_logout')]
    public function logout(SessionInterface $session): Response
    {
        $session->invalidate();
        return $this->redirectToRoute('app_admin_login');
    }
}