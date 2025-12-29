<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

class DashboardController extends AbstractController
{
    /*#[Route('/admin', name: 'app_dashboard')]
    public function index(Connection $connection, SessionInterface $session): Response
    {
        // ✅ PROTECTION IMMÉDIATE : Si pas connecté → PAGE DE CONNEXION
        if (!$session->get('admin_logged_in')) {
            return $this->redirectToRoute('app_admin_login');
        }

        try {
            $aujourdhui = date('Y-m-d');
            $metriques = $this->getMetriquesJournalieres($connection, $aujourdhui);
            $commandesRecentes = $this->getCommandesRecentesJour($connection, $aujourdhui);
            $topBurgers = $this->getTopBurgersJour($connection, $aujourdhui);

            return $this->render('admin/dashboard.html.twig', [
                'metriques' => $metriques,
                'commandesRecentes' => $commandesRecentes,
                'topBurgers' => $topBurgers,
                'dateAujourdhui' => $aujourdhui
            ]);

        } catch (\Exception $e) {
            return $this->render('admin/dashboard.html.twig', [
                'metriques' => [
                    'commandes_jour' => 0,
                    'recettes_jour' => 0,
                    'commandes_en_cours' => 0,
                    'commandes_annulees' => 0
                ],
                'commandesRecentes' => [],
                'topBurgers' => [],
                'error' => $e->getMessage()
            ]);
        }
    }*/

    #[Route('/admin', name: 'app_dashboard')]
    public function index(Connection $connection, SessionInterface $session): Response
    {
        // ✅ PROTECTION : Si pas connecté → PAGE DE CONNEXION
        if (!$session->get('admin_logged_in')) {
            return $this->redirectToRoute('app_admin_login');
        }
    
        try {
            $aujourdhui = date('Y-m-d');
            $metriques = $this->getMetriquesJournalieres($connection, $aujourdhui);
            $commandesRecentes = $this->getCommandesRecentesJour($connection, $aujourdhui);
            $topBurgers = $this->getTopBurgersJour($connection, $aujourdhui);
    
            return $this->render('admin/dashboard.html.twig', [
                'metriques' => $metriques,
                'commandesRecentes' => $commandesRecentes,
                'topBurgers' => $topBurgers,
                'dateAujourdhui' => $aujourdhui
            ]);
    
        } catch (\Exception $e) {
            return $this->render('admin/dashboard.html.twig', [
                'metriques' => [
                    'commandes_jour' => 0,
                    'recettes_jour' => 0,
                    'commandes_en_cours' => 0,
                    'commandes_annulees' => 0
                ],
                'commandesRecentes' => [],
                'topBurgers' => [],
                'error' => $e->getMessage()
            ]);
        }
    }





    // Vos méthodes existantes restent identiques...
    private function getMetriquesJournalieres(Connection $connection, string $date): array
    {
        $commandesJour = (int) $connection->fetchOne("
            SELECT COUNT(*) 
            FROM commande 
            WHERE DATE(date_commande) = ? 
            AND statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
        ", [$date]) ?: 0;

        $recettesJour = (float) $connection->fetchOne("
            SELECT COALESCE(SUM(montant_total), 0) 
            FROM commande 
            WHERE DATE(date_commande) = ? 
            AND statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
        ", [$date]) ?: 0;

        $commandesEnCours = (int) $connection->fetchOne("
            SELECT COUNT(*) 
            FROM commande 
            WHERE DATE(date_commande) = ? 
            AND statut IN ('VALIDEE', 'EN_COURS', 'PRETE')
        ", [$date]) ?: 0;

        $commandesAnnulees = (int) $connection->fetchOne("
            SELECT COUNT(*) 
            FROM commande 
            WHERE DATE(date_commande) = ? 
            AND statut = 'ANNULEE'
        ", [$date]) ?: 0;

        return [
            'commandes_jour' => $commandesJour,
            'recettes_jour' => $recettesJour,
            'recettes_jour_formate' => number_format($recettesJour, 0, ',', ' '),
            'commandes_en_cours' => $commandesEnCours,
            'commandes_annulees' => $commandesAnnulees
        ];
    }

    private function getCommandesRecentesJour(Connection $connection, string $date): array
    {
        $commandes = $connection->fetchAllAssociative("
            SELECT c.id, c.client_id, c.montant_total, c.statut, 
                   c.date_commande, c.type_consommation,
                   '#CMD-' || LPAD(c.id::text, 6, '0') as numero
            FROM commande c 
            WHERE DATE(c.date_commande) = ?
            ORDER BY c.date_commande DESC
            LIMIT 5
        ", [$date]);

        foreach ($commandes as &$commande) {
            $commande['client_nom'] = 'Client ' . $commande['client_id'];
            $commande['client_initiales'] = 'C' . $commande['client_id'];
            $commande['prix_formate'] = number_format($commande['montant_total'], 0, ',', ' ');
            $commande['heure'] = date('H:i', strtotime($commande['date_commande']));
            $commande['status_badge'] = $this->getStatusBadge($commande['statut']);
            
            $produitExemple = $connection->fetchOne("
                SELECT p.nom 
                FROM ligne_commande lc
                JOIN produit p ON lc.produit_id = p.id
                WHERE lc.commande_id = ?
                LIMIT 1
            ", [$commande['id']]);
            
            $commande['produit_exemple'] = $produitExemple ?: 'Produit inconnu';
        }

        return $commandes;
    }

    private function getTopBurgersJour(Connection $connection, string $date): array
    {
        try {
            $topProduits = $connection->fetchAllAssociative("
                SELECT 
                    p.nom as produit,
                    COUNT(*) as ventes_jour,
                    SUM(lc.prix_unitaire * lc.quantite) as chiffre_affaires_jour
                FROM ligne_commande lc
                JOIN produit p ON lc.produit_id = p.id  
                JOIN commande c ON lc.commande_id = c.id
                WHERE DATE(c.date_commande) = ?
                    AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                    AND p.type_produit IN ('BURGER', 'MENU')
                GROUP BY p.id, p.nom
                ORDER BY ventes_jour DESC
                LIMIT 5
            ", [$date]);

            foreach ($topProduits as &$produit) {
                $produit['prix_formate'] = number_format($produit['chiffre_affaires_jour'], 0, ',', ' ');
            }

            return $topProduits;

        } catch (\Exception $e) {
            return [];
        }
    }

    private function getStatusBadge(?string $statut): array
    {
        return match($statut) {
            'VALIDEE' => ['text' => 'Validée', 'class' => 'validee'],
            'EN_COURS' => ['text' => 'En cours', 'class' => 'en-cours'],
            'PRETE' => ['text' => 'Prête', 'class' => 'prete'],
            'LIVREE', 'TERMINEE' => ['text' => 'Terminée', 'class' => 'terminee'],
            'ANNULEE' => ['text' => 'Annulée', 'class' => 'annulee'],
            default => ['text' => 'En attente', 'class' => 'en-attente']
        };
    }
}