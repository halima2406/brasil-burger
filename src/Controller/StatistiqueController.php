<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

class StatistiqueController extends AbstractController
{
    #[Route('/admin/statistiques', name: 'app_statistiques')]
    public function index(Connection $connection): Response
    {
        try {
            $statsJour = $this->getStatistiquesJournalieres($connection);
            $topVentes = $this->getTopBurgersMenusJour($connection);
            $recettesJour = $this->getRecettesJournalieres($connection);
            
            $data = [
                'topVentes' => $topVentes,
                'recettesJouralieres' => $recettesJour,
                'commandesValidees' => [],
                'commandesAnnulees' => [],
                'totalVentes' => $statsJour['commandes_validees'],
                'totalRecettes' => $statsJour['recettes_jour'],
                'stats_jour' => $statsJour
            ];
            
            return $this->render('admin/statistiques/index.html.twig', $data);
            
        } catch (\Exception $e) {
            return $this->render('admin/statistiques/index.html.twig', [
                'error' => $e->getMessage(),
                'topVentes' => [],
                'recettesJouralieres' => ['burgers' => 0, 'menus' => 0, 'frites' => 0, 'boissons' => 0, 'total' => 0]
            ]);
        }
    }

    private function getStatistiquesJournalieres(Connection $connection): array
    {
        $aujourdhui = date('Y-m-d');
        
        $commandesValidees = $connection->fetchOne("
            SELECT COUNT(*) FROM commande 
            WHERE DATE(date_commande) = ? AND statut = 'VALIDEE'
        ", [$aujourdhui]) ?: 0;
        
        $commandesAnnulees = $connection->fetchOne("
            SELECT COUNT(*) FROM commande 
            WHERE DATE(date_commande) = ? AND statut = 'ANNULEE'
        ", [$aujourdhui]) ?: 0;
        
        $recettesJour = $connection->fetchOne("
            SELECT COALESCE(SUM(montant_total), 0) FROM commande 
            WHERE DATE(date_commande) = ? AND statut IN ('VALIDEE', 'EN_COURS', 'TERMINEE')
        ", [$aujourdhui]) ?: 0;
        
        return [
            'commandes_validees' => (int) $commandesValidees,
            'commandes_annulees' => (int) $commandesAnnulees,
            'recettes_jour' => (float) $recettesJour
        ];
    }

    private function getTopBurgersMenusJour(Connection $connection): array
    {
        $aujourdhui = date('Y-m-d');
        
        $results = $connection->fetchAllAssociative("
            SELECT 
                p.nom as produit,
                COUNT(*) as ventes_journee,
                SUM(lc.prix_unitaire * lc.quantite) as chiffre_affaires_jour
            FROM ligne_commande lc
            JOIN produit p ON lc.produit_id = p.id  
            JOIN commande c ON lc.commande_id = c.id
            WHERE DATE(c.date_commande) = ?
                AND c.statut IN ('VALIDEE', 'EN_COURS', 'TERMINEE')
                AND p.type_produit IN ('BURGER', 'MENU')
            GROUP BY p.id, p.nom
            ORDER BY ventes_journee DESC
            LIMIT 5
        ", [$aujourdhui]);
        
        $topVentes = [];
        $rang = 1;
        
        foreach ($results as $result) {
            $topVentes[] = [
                'rang' => $rang,
                'classe_rang' => $this->getClasseRang($rang),
                'nom' => $result['produit'],
                'total_vendu' => (int) $result['ventes_journee'],
                'chiffre_affaires' => (int) $result['chiffre_affaires_jour'],
                'image_url' => 'https://images.unsplash.com/photo-1572802419224-296b0aeee0d9?w=100'
            ];
            $rang++;
        }
        
        return $topVentes;
    }

    private function getRecettesJournalieres(Connection $connection): array
    {
        $aujourdhui = date('Y-m-d');
        $recettes = ['burgers' => 0, 'menus' => 0, 'frites' => 0, 'boissons' => 0, 'total' => 0];
        
       
        $resultsProduits = $connection->fetchAllAssociative("
            SELECT 
                p.type_produit,
                SUM(lc.prix_unitaire * lc.quantite) as recette
            FROM ligne_commande lc
            JOIN produit p ON lc.produit_id = p.id  
            JOIN commande c ON lc.commande_id = c.id
            WHERE DATE(c.date_commande) = ?
                AND c.statut IN ('VALIDEE', 'EN_COURS', 'TERMINEE')
                AND lc.produit_id IS NOT NULL
            GROUP BY p.type_produit
        ", [$aujourdhui]);
        
        foreach ($resultsProduits as $result) {
            $montant = (int) $result['recette'];
            switch (strtoupper($result['type_produit'])) {
                case 'BURGER':
                    $recettes['burgers'] = $montant;
                    break;
                case 'FRITE':
                    $recettes['frites'] = $montant;
                    break;
                case 'BOISSON':
                    $recettes['boissons'] = $montant;
                    break;
            }
        }
        
        
        $recetteMenus = $connection->fetchOne("
            SELECT SUM(lc.prix_unitaire * lc.quantite) as recette_menus
            FROM ligne_commande lc
            JOIN commande c ON lc.commande_id = c.id
            WHERE DATE(c.date_commande) = ?
                AND c.statut IN ('VALIDEE', 'EN_COURS', 'TERMINEE')
                AND lc.menu_id IS NOT NULL
        ", [$aujourdhui]);
        
        $recettes['menus'] = (int) ($recetteMenus ?: 0);
        $recettes['total'] = $recettes['burgers'] + $recettes['menus'] + $recettes['frites'] + $recettes['boissons'];
        
        return $recettes;
    }

    private function getClasseRang(int $rang): string
    {
        return match($rang) {
            1 => 'gold',
            2 => 'silver', 
            3 => 'bronze',
            default => ''
        };
    }
}