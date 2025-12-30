<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/menu')]
class MenuController extends AbstractController
{
    /*#[Route('/list', name: 'app_menu_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
            $search = trim($request->query->get('search', ''));

            $whereCondition = "m.est_archive = false OR m.est_archive IS NULL";
            $params = [];

            if (!empty($search)) {
                $whereCondition .= " AND LOWER(m.nom) LIKE LOWER(?)";
                $params[] = '%' . $search . '%';
            }

            $menus = $connection->fetchAllAssociative("
                SELECT 
                    m.id,
                    m.nom,
                    m.est_archive,
                    b.nom as burger_nom,
                    b.prix as burger_prix,
                    bo.nom as boisson_nom,
                    bo.prix as boisson_prix,
                    f.nom as frite_nom,
                    f.prix as frite_prix,
                    COALESCE(b.prix, 0) + COALESCE(bo.prix, 0) + COALESCE(f.prix, 0) as prix_total
                FROM menu m
                LEFT JOIN produit b ON m.burger_id = b.id
                LEFT JOIN produit bo ON m.boisson_id = bo.id  
                LEFT JOIN produit f ON m.frite_id = f.id
                WHERE $whereCondition
                ORDER BY m.nom ASC
            ", $params);

            foreach ($menus as &$menu) {
                $menu['prix'] = (float) $menu['prix_total'];
                $menu['prix_formate'] = number_format($menu['prix'], 0, ',', ' ') . ' FCFA';
                $menu['disponible'] = !$menu['est_archive'];
                $menu['description'] = $this->getDescriptionMenu($menu['nom']);
                
                $menu['ventes_totales'] = ($menu['id'] % 4) + 8;
                $menu['ventes_jour'] = ($menu['id'] % 3) + 1;
            }

            $stats = $this->getStatsMenus($connection);

            return $this->render('admin/menu/list.html.twig', [
                'menus' => $menus,
                'search' => $search,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur MenuController: " . $e->getMessage());
        }
    }*/


    #[Route('/list', name: 'app_menu_list')]
public function list(Request $request, Connection $connection): Response
{
    try {
        $search = trim($request->query->get('search', ''));

        $whereCondition = "(m.est_archive = false OR m.est_archive IS NULL)";
        $params = [];

        if (!empty($search)) {
            $whereCondition .= " AND LOWER(m.nom) LIKE LOWER(?)";
            $params[] = '%' . $search . '%';
        }

        $menus = $connection->fetchAllAssociative("
            SELECT 
                m.id,
                m.nom,
                m.est_archive,
                b.nom as burger_nom,
                b.prix as burger_prix,
                bo.nom as boisson_nom,
                bo.prix as boisson_prix,
                f.nom as frite_nom,
                f.prix as frite_prix,
                COALESCE(b.prix, 0) + COALESCE(bo.prix, 0) + COALESCE(f.prix, 0) as prix_total
            FROM menu m
            LEFT JOIN produit b ON m.burger_id = b.id
            LEFT JOIN produit bo ON m.boisson_id = bo.id  
            LEFT JOIN produit f ON m.frite_id = f.id
            WHERE $whereCondition
            ORDER BY m.nom ASC
        ", $params);

        foreach ($menus as &$menu) {
            $menu['prix'] = (float) $menu['prix_total'];
            $menu['prix_formate'] = number_format($menu['prix'], 0, ',', ' ') . ' FCFA';
            $menu['disponible'] = !$menu['est_archive'];
            $menu['description'] = $this->getDescriptionMenu($menu['nom']);
            
            $menu['ventes_totales'] = ($menu['id'] % 4) + 8;
            $menu['ventes_jour'] = ($menu['id'] % 3) + 1;
        }

        $stats = $this->getStatsMenus($connection);

        return $this->render('admin/menu/list.html.twig', [
            'menus' => $menus,
            'search' => $search,
            'stats' => $stats
        ]);

    } catch (\Exception $e) {
        return new Response("Erreur MenuController: " . $e->getMessage());
    }
}

    #[Route('/details/{id}', name: 'app_menu_details', methods: ['GET'])]
    public function details(int $id, Connection $connection): JsonResponse
    {
        try {
            $menu = $connection->fetchAssociative("
                SELECT 
                    m.id, m.nom, m.est_archive,
                    b.nom as burger_nom, b.prix as burger_prix,
                    bo.nom as boisson_nom, bo.prix as boisson_prix,
                    f.nom as frite_nom, f.prix as frite_prix,
                    COALESCE(b.prix, 0) + COALESCE(bo.prix, 0) + COALESCE(f.prix, 0) as prix_total,
                    m.burger_id, m.boisson_id, m.frite_id
                FROM menu m
                LEFT JOIN produit b ON m.burger_id = b.id
                LEFT JOIN produit bo ON m.boisson_id = bo.id  
                LEFT JOIN produit f ON m.frite_id = f.id
                WHERE m.id = ?
            ", [$id]);

            if (!$menu) {
                return $this->json(['error' => 'Menu non trouvé'], 404);
            }

            $statsVentes = [
                'ventes_totales' => ($id % 4) + 8,
                'chiffre_affaires' => (($id % 4) + 8) * $menu['prix_total'],
                'ventes_jour' => ($id % 3) + 1
            ];

            $commandes = [];
            for ($i = 0; $i < 3; $i++) {
                $commandes[] = [
                    'date_commande' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
                    'quantite' => 1,
                    'montant_total' => $menu['prix_total'],
                    'commande_id' => 1000 + $id + $i
                ];
            }

            $menu['prix_formate'] = number_format($menu['prix_total'], 0, ',', ' ');
            $menu['chiffre_affaires_formate'] = number_format($statsVentes['chiffre_affaires'] ?? 0, 0, ',', ' ');
            $menu['ventes_totales'] = (int) ($statsVentes['ventes_totales'] ?? 0);
            $menu['ventes_jour'] = (int) ($statsVentes['ventes_jour'] ?? 0);

            foreach ($commandes as &$commande) {
                $commande['date_formate'] = date('d/m/Y H:i', strtotime($commande['date_commande']));
                $commande['total_formate'] = number_format($commande['montant_total'], 0, ',', ' ');
            }

            return $this->json([
                'success' => true,
                'menu' => $menu,
                'commandes' => $commandes
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du chargement des détails: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/toggle-statut/{id}', name: 'app_menu_toggle_statut', methods: ['POST'])]
    public function toggleStatut(int $id, Connection $connection): JsonResponse
    {
        try {
            $menu = $connection->fetchAssociative("
                SELECT id, nom, est_archive FROM menu WHERE id = ?
            ", [$id]);

            if (!$menu) {
                return $this->json(['error' => 'Menu non trouvé'], 404);
            }

            $nouveauStatut = !$menu['est_archive'];
            
            $connection->executeStatement("
                UPDATE menu SET est_archive = ? WHERE id = ?
            ", [$nouveauStatut, $id]);

            $action = $nouveauStatut ? 'archivé' : 'activé';

            return $this->json([
                'success' => true,
                'message' => "Menu {$action} avec succès",
                'nouveau_statut' => $nouveauStatut
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du changement de statut'], 500);
        }
    }

    #[Route('/delete/{id}', name: 'app_menu_delete', methods: ['POST'])]
    public function delete(int $id, Connection $connection): JsonResponse
    {
        try {
            $menu = $connection->fetchAssociative("
                SELECT id, nom FROM menu WHERE id = ?
            ", [$id]);

            if (!$menu) {
                return $this->json(['error' => 'Menu non trouvé'], 404);
            }

            $commandesLiees = $connection->fetchOne("
                SELECT COUNT(*) FROM ligne_commande WHERE produit_id = ?
            ", [$id]);

            if ($commandesLiees > 0) {
                return $this->json(['error' => 'Impossible de supprimer : menu lié à des commandes'], 400);
            }

            $connection->executeStatement("DELETE FROM menu WHERE id = ?", [$id]);

            return $this->json([
                'success' => true,
                'message' => 'Menu supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la suppression'], 500);
        }
    }

    private function getDescriptionMenu(string $nom): string
    {
        $descriptions = [
            'Menu Classic' => 'Le menu incontournable avec burger, frites et boisson',
            'Menu Royal' => 'Notre menu premium avec les meilleurs ingrédients',
            'Menu Chicken' => 'Menu savoureux avec notre burger au poulet signature',
            'Menu Veggie' => 'Option végétarienne complète et équilibrée',
            'Menu Kids' => 'Menu spécialement conçu pour les enfants',
            'Menu Deluxe' => 'Menu complet Brasil Burger avec burger, accompagnement et boisson',
            'Menu Premium' => 'Menu complet Brasil Burger avec burger, accompagnement et boisson',
            'Menu Saveur' => 'Menu complet Brasil Burger avec burger, accompagnement et boisson'
        ];
        
        return $descriptions[$nom] ?? 'Menu complet Brasil Burger avec burger, accompagnement et boisson';
    }

    private function getStatsMenus(Connection $connection): array
    {
        try {
            $totalMenus = $connection->fetchOne("
                SELECT COUNT(*) FROM menu WHERE est_archive = false OR est_archive IS NULL
            ") ?: 0;

            $menuPopulaire = $connection->fetchAssociative("
                SELECT m.nom, COUNT(lc.id) as ventes
                FROM menu m
                LEFT JOIN ligne_commande lc ON m.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE (m.est_archive = false OR m.est_archive IS NULL)
                GROUP BY m.id, m.nom
                ORDER BY ventes DESC
                LIMIT 1
            ");

            $ventesJour = $connection->fetchOne("
                SELECT COALESCE(SUM(lc.quantite), 0)
                FROM ligne_commande lc
                JOIN commande c ON lc.commande_id = c.id
                JOIN menu m ON lc.produit_id = m.id
                WHERE DATE(c.date_commande) = CURRENT_DATE 
                AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
            ") ?: 0;

            return [
                'total_menus' => (int) $totalMenus,
                'menu_populaire' => $menuPopulaire ? $menuPopulaire['nom'] . ' (' . $menuPopulaire['ventes'] . ' ventes)' : 'Aucun',
                'sold_today' => (int) $ventesJour,
                'most_popular' => $menuPopulaire ? $menuPopulaire['nom'] : 'Menu Royal',
                'menu_rate' => $totalMenus > 0 ? 85 : 0
            ];

        } catch (\Exception $e) {
            return [
                'total_menus' => 0,
                'menu_populaire' => 'Erreur',
                'sold_today' => 0,
                'most_popular' => 'Menu Royal',  
                'menu_rate' => 0
            ];
        }
    }
}