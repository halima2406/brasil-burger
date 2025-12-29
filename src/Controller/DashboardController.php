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
        $commandes = $this->getCommandes($connection, $aujourdhui);

        return new Response('<h1>Dashboard Brasil Burger</h1><p>Commandes du jour: ' . $metriques['commandes_jour'] . '</p><p>Recettes: ' . $metriques['recettes_jour_formate'] . ' FCFA</p><p>Dernières commandes: ' . count($commandes) . '</p><a href="/admin/logout">Se déconnecter</a>');
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

    private function getCommandes(Connection $connection, string $date): array
    {
        $commandes = $connection->fetchAllAssociative("
            SELECT c.id, c.client_id, c.montant_total, c.statut, c.date_commande
            FROM commande c WHERE DATE(c.date_commande) = ? ORDER BY c.date_commande DESC LIMIT 5
        ", [$date]);

        foreach ($commandes as &$commande) {
            $commande['client_nom'] = 'Client ' . $commande['client_id'];
            $commande['client_initiales'] = 'C' . $commande['client_id'];
            $commande['prix_formate'] = number_format($commande['montant_total'], 0, ',', ' ');
            $commande['heure'] = date('H:i', strtotime($commande['date_commande']));
            $commande['produit_exemple'] = 'Produit test';
        }

        return $commandes;
    }
}