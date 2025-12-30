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
    #[Route('/list', name: 'app_menu_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $perPage = 5;
            $offset = ($page - 1) * $perPage;

        
            $totalMenus = $connection->fetchOne("
                SELECT COUNT(*) FROM menu m 
                WHERE m.est_archive = false OR m.est_archive IS NULL
            ") ?: 0;

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
                    COALESCE(b.prix, 0) + COALESCE(bo.prix, 0) + COALESCE(f.prix, 0) as prix_total,
                    COALESCE(SUM(lc.quantite), 0) as ventes_totales,
                    COALESCE(SUM(CASE WHEN DATE(c.date_commande) = CURRENT_DATE THEN lc.quantite ELSE 0 END), 0) as ventes_jour
                FROM menu m
                LEFT JOIN produit b ON m.burger_id = b.id
                LEFT JOIN produit bo ON m.boisson_id = bo.id  
                LEFT JOIN produit f ON m.frite_id = f.id
                LEFT JOIN ligne_commande lc ON m.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE m.est_archive = false OR m.est_archive IS NULL
                GROUP BY m.id, m.nom, m.est_archive, b.nom, b.prix, bo.nom, bo.prix, f.nom, f.prix
                ORDER BY m.nom ASC
                LIMIT $perPage OFFSET $offset
            ");

            foreach ($menus as &$menu) {
                $menu['prix'] = (float) $menu['prix_total'];
                $menu['prix_formate'] = number_format($menu['prix'], 0, ',', ' ') . ' FCFA';
                $menu['disponible'] = !$menu['est_archive'];
                $menu['description'] = $this->getDescriptionMenu($menu['nom']);
                $menu['ventes_totales'] = (int) $menu['ventes_totales'];
                $menu['ventes_jour'] = (int) $menu['ventes_jour'];
            }

            $stats = $this->getStatsMenus($connection);

         
            $totalPages = ceil($totalMenus / $perPage);
            $pagination = [
                'pageEnCours' => $page,
                'nbrePage' => $totalPages,
            ];

            return $this->render('admin/menu/list.html.twig', array_merge([
                'menus' => $menus,
                'totalMenus' => $totalMenus,
                'stats' => $stats
            ], $pagination));

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
                    COALESCE(SUM(lc.quantite), 0) as ventes_totales,
                    COALESCE(SUM(lc.montant_total), 0) as chiffre_affaires,
                    COALESCE(SUM(CASE WHEN DATE(c.date_commande) = CURRENT_DATE THEN lc.quantite ELSE 0 END), 0) as ventes_jour
                FROM menu m
                LEFT JOIN produit b ON m.burger_id = b.id
                LEFT JOIN produit bo ON m.boisson_id = bo.id  
                LEFT JOIN produit f ON m.frite_id = f.id
                LEFT JOIN ligne_commande lc ON m.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE m.id = ?
                GROUP BY m.id, m.nom, m.est_archive, b.nom, b.prix, bo.nom, bo.prix, f.nom, f.prix
            ", [$id]);

            if (!$menu) {
                return $this->json(['error' => 'Menu non trouvé'], 404);
            }

           
            $commandes = $connection->fetchAllAssociative("
                SELECT c.date_commande, lc.quantite, lc.montant_total
                FROM ligne_commande lc
                JOIN commande c ON lc.commande_id = c.id
                WHERE lc.produit_id = ? AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                ORDER BY c.date_commande DESC
                LIMIT 10
            ", [$id]);

          
            $menu['prix_formate'] = number_format($menu['prix_total'], 0, ',', ' ');
            $menu['chiffre_affaires_formate'] = number_format($menu['chiffre_affaires'], 0, ',', ' ');
            $menu['ventes_totales'] = (int) $menu['ventes_totales'];
            $menu['ventes_jour'] = (int) $menu['ventes_jour'];

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
            return $this->json(['error' => 'Erreur lors du chargement des détails'], 500);
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
            'Menu Kids' => 'Menu spécialement conçu pour les enfants'
        ];
        
        return $descriptions[$nom] ?? 'Menu complet Brasil Burger avec burger, accompagnement et boisson';
    }

   
    #[Route('/seed', name: 'app_complement_seed')]
    public function seed(Connection $connection): Response
    {
        try {
            $existants = $connection->fetchOne("
                SELECT COUNT(*) FROM produit WHERE type_produit IN ('ACCOMPAGNEMENT', 'BOISSON')
            ");
            
            if ($existants > 0) {
                return new Response("
                    <h1>✅ Compléments déjà présents</h1>
                    <p><strong>$existants compléments</strong> trouvés dans la base.</p>
                    <a href='/admin/menu/list'>Voir les menus</a>
                ");
            }
            
            $accompagnements = [
                ['Frites Classiques', 800],
                ['Frites Épicées', 1000],
                ['Salade César', 1200],
                ['Onion Rings', 1100]
            ];
            
            foreach ($accompagnements as [$nom, $prix]) {
                $connection->executeStatement("
                    INSERT INTO produit (nom, prix, type_produit) VALUES (?, ?, 'ACCOMPAGNEMENT')
                ", [$nom, $prix]);
            }
            
            $boissons = [
                ['Coca-Cola', 500],
                ['Sprite', 500],
                ['Jus d\'Orange', 700],
                ['Eau Minérale', 300]
            ];
            
            foreach ($boissons as [$nom, $prix]) {
                $connection->executeStatement("
                    INSERT INTO produit (nom, prix, type_produit) VALUES (?, ?, 'BOISSON')
                ", [$nom, $prix]);
            }
            
            return new Response("
                <h1>🎉 Compléments créés avec succès !</h1>
                <p>Accompagnements et boissons ajoutés pour les menus.</p>
                <a href='/admin/menu/list'>Voir les menus</a>
            ");
            
        } catch (\Exception $e) {
            return new Response("Erreur: " . $e->getMessage());
        }
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