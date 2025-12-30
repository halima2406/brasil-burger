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
        $topBurgers = $this->getTopBurgers($connection, $aujourdhui);

        return $this->render('admin/dashboard.html.twig', [
            'metriques' => $metriques,
            'commandesRecentes' => $commandes,
            'topBurgers' => $topBurgers,
            'dateAujourdhui' => $aujourdhui
        ]);
    }

    private function getMetriques(Connection $connection, string $date): array
    {
        $commandesJour = $connection->fetchOne("SELECT COUNT(*) FROM commande WHERE DATE(date_commande) = ?", [$date]) ?: 0;
        $recettesJour = $connection->fetchOne("SELECT COALESCE(SUM(montant_total), 0) FROM commande WHERE DATE(date_commande) = ?", [$date]) ?: 0;
        
       
        $commandesEnCours = $connection->fetchOne("
            SELECT COUNT(*) FROM commande 
            WHERE DATE(date_commande) = ? AND statut IN ('VALIDEE', 'EN_COURS', 'PRETE')
        ", [$date]) ?: 0;
        
        $commandesAnnulees = $connection->fetchOne("
            SELECT COUNT(*) FROM commande 
            WHERE DATE(date_commande) = ? AND statut = 'ANNULEE'
        ", [$date]) ?: 0;
        
        return [
            'commandes_jour' => $commandesJour,
            'recettes_jour' => $recettesJour,
            'recettes_jour_formate' => number_format($recettesJour, 0, ',', ' '),
            'commandes_en_cours' => $commandesEnCours,
            'commandes_annulees' => $commandesAnnulees
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
            $commande['status_badge'] = $this->getStatusBadge($commande['statut']);
            $commande['produit_exemple'] = 'Produit test';
        }

        return $commandes;
    }

    private function getTopBurgers(Connection $connection, string $date): array
    {
        try {
            $topBurgers = $connection->fetchAllAssociative("
                SELECT 
                    p.nom as produit,
                    p.prix,
                    SUM(lc.quantite) as ventes_jour,
                    SUM(lc.montant_total) as chiffre_affaires
                FROM ligne_commande lc
                JOIN commande c ON lc.commande_id = c.id
                JOIN produit p ON lc.produit_id = p.id
                WHERE DATE(c.date_commande) = ?
                AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                AND p.type_produit = 'BURGER'
                GROUP BY p.id, p.nom, p.prix
                ORDER BY ventes_jour DESC, chiffre_affaires DESC
                LIMIT 5
            ", [$date]);

            foreach ($topBurgers as &$burger) {
                $burger['prix_formate'] = number_format($burger['prix'], 0, ',', ' ');
                $burger['chiffre_affaires_formate'] = number_format($burger['chiffre_affaires'], 0, ',', ' ');
            }

            return $topBurgers;

        } catch (\Exception $e) {
            return [];
        }
    }

    private function getStatusBadge(?string $statut): array
    {
        return match($statut) {
            'VALIDEE' => ['text' => 'Validée', 'class' => 'pending'],
            'EN_COURS' => ['text' => 'En cours', 'class' => 'preparing'],
            'PRETE' => ['text' => 'Prête', 'class' => 'delivering'],
            'LIVREE', 'TERMINEE' => ['text' => 'Terminée', 'class' => 'completed'],
            'ANNULEE' => ['text' => 'Annulée', 'class' => 'cancelled'],
            default => ['text' => 'En attente', 'class' => 'pending']
        };
    }
}