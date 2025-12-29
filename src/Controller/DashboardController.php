<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'app_dashboard')]
    public function index(Connection $connection, SessionInterface $session): Response
    {
        if (!$session->get('admin_logged_in')) {
            return $this->redirectToRoute('app_admin_login');
        }

        $aujourdhui = date('Y-m-d');
        $metriques = $this->getMetriques($connection, $aujourdhui);

        return new Response('<h1>Dashboard Brasil Burger</h1><p>Commandes du jour: ' . $metriques['commandes_jour'] . '</p><p>Recettes: ' . $metriques['recettes_jour_formate'] . ' FCFA</p><a href="/admin/logout">Se déconnecter</a>');
    }

    private function getMetriques(Connection $connection, string $date): array
    {
        $commandesJour = $connection->fetchOne("SELECT COUNT(*) FROM commande WHERE DATE(date_commande) = ?", [$date]) ?: 0;
        $recettesJour = $connection->fetchOne("SELECT COALESCE(SUM(montant_total), 0) FROM commande WHERE DATE(date_commande) = ?", [$date]) ?: 0;
        
        return [
            'commandes_jour' => $commandesJour,
            'recettes_jour' => $recettesJour,
            'recettes_jour_formate' => number_format($recettesJour, 0, ',', ' '),
            'commandes_en_cours' => 0,
            'commandes_annulees' => 0
        ];
    }
}