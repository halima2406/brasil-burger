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
            // ✅ TOUTES LES MÉTRIQUES DU JOUR
            $statsJour = $this->getStatistiquesJournalieres($connection);
            $topVentes = $this->getTopBurgersMenusJour($connection);
            $recettesJour = $this->getRecettesJournalieres($connection);
            
            $data = [
                'topVentes' => $topVentes,
                'recettesJouralieres' => $recettesJour,
                'commandesValidees' => [],
                'commandesAnnulees' => [],
                'totalVentes' => $statsJour['commandes_en_cours'],
                'totalRecettes' => $statsJour['recettes_jour'],
                'stats_jour' => $statsJour, // ✅ NOUVELLES STATS
                
                // Debug
                'debug' => [
                    'date_du_jour' => date('Y-m-d'),
                    'stats_calculees' => $statsJour
                ]
            ];
            
            return $this->render('admin/statistiques/index.html.twig', $data);
            
        } catch (\Exception $e) {
            return $this->render('admin/statistiques/index.html.twig', [
                'error' => $e->getMessage(),
                'topVentes' => [],
                'recettesJouralieres' => ['burgers' => 0, 'menus' => 0, 'frites' => 0, 'boissons' => 0, 'total' => 0],
                'stats_jour' => [],
                'debug' => ['erreur' => $e->getMessage()]
            ]);
        }
    }

    // ✅ STATISTIQUES JOURNALIÈRES COMPLÈTES
    private function getStatistiquesJournalieres(Connection $connection): array
    {
        $aujourdhui = date('Y-m-d');
        
        return [
            'commandes_en_cours' => (int) $connection->fetchOne("
                SELECT COUNT(*) FROM commande 
                WHERE DATE(date_commande) = ? AND statut IN ('VALIDEE', 'EN_COURS')
            ", [$aujourdhui]) ?: 0,
            
            'commandes_validees' => (int) $connection->fetchOne("
                SELECT COUNT(*) FROM commande 
                WHERE DATE(date_commande) = ? AND statut = 'VALIDEE'
            ", [$aujourdhui]) ?: 0,
            
            'commandes_annulees' => (int) $connection->fetchOne("
                SELECT COUNT(*) FROM commande 
                WHERE DATE(date_commande) = ? AND statut = 'ANNULEE'
            ", [$aujourdhui]) ?: 0,
            
            'recettes_jour' => (float) $connection->fetchOne("
                SELECT COALESCE(SUM(montant_total), 0) FROM commande 
                WHERE DATE(date_commande) = ? AND statut IN ('VALIDEE', 'EN_COURS', 'TERMINEE')
            ", [$aujourdhui]) ?: 0
        ];
    }

    // ✅ TOP BURGERS & MENUS DU JOUR
    private function getTopBurgersMenusJour(Connection $connection): array
    {
        try {
            $aujourdhui = date('Y-m-d');
            
            $sql = "
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
            ";
            
            $results = $connection->fetchAllAssociative($sql, [$aujourdhui]);
            
            $topVentes = [];
            $rang = 1;
            foreach ($results as $result) {
                $topVentes[] = [
                    'rang' => $rang,
                    'classe_rang' => $this->getClasseRang($rang),
                    'nom' => $result['produit'],
                    'total_vendu' => (int)$result['ventes_journee'],
                    'chiffre_affaires' => (int)$result['chiffre_affaires_jour'],
                    'image_url' => 'https://images.unsplash.com/photo-1572802419224-296b0aeee0d9?w=100'
                ];
                $rang++;
            }
            
            return $topVentes;
            
        } catch (\Exception $e) {
            return [];
        }
    }

    // ✅ RECETTES JOURNALIÈRES PAR CATÉGORIE
    // ✅ RECETTES JOURNALIÈRES PAR CATÉGORIE CORRIGÉE
    private function getRecettesJournalieres(Connection $connection): array
    {
        try {
            $aujourdhui = date('Y-m-d');
            
            $recettes = ['burgers' => 0, 'menus' => 0, 'frites' => 0, 'boissons' => 0, 'total' => 0];
            
            // 1. Recettes des PRODUITS (burgers, frites, boissons)
            $sqlProduits = "
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
            ";
            
            $resultsProduits = $connection->fetchAllAssociative($sqlProduits, [$aujourdhui]);
            
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
            
            // 2. Recettes des MENUS (via menu_id)
            $sqlMenus = "
                SELECT SUM(lc.prix_unitaire * lc.quantite) as recette_menus
                FROM ligne_commande lc
                JOIN commande c ON lc.commande_id = c.id
                WHERE DATE(c.date_commande) = ?
                    AND c.statut IN ('VALIDEE', 'EN_COURS', 'TERMINEE')
                    AND lc.menu_id IS NOT NULL
            ";
            
            $recetteMenus = (int) $connection->fetchOne($sqlMenus, [$aujourdhui]);
            $recettes['menus'] = $recetteMenus ?: 0;
            
            // 3. Calcul du total
            $recettes['total'] = $recettes['burgers'] + $recettes['menus'] + $recettes['frites'] + $recettes['boissons'];
            
            return $recettes;
            
        } catch (\Exception $e) {
            return ['burgers' => 0, 'menus' => 0, 'frites' => 0, 'boissons' => 0, 'total' => 0];
        }
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