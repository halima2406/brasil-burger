<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/menu')]
class MenuController extends AbstractController
{
    #[Route('/list', name: 'app_menu_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
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
                WHERE m.est_archive = false OR m.est_archive IS NULL
                ORDER BY m.nom ASC
                LIMIT 10
            ");

            foreach ($menus as &$menu) {
                $menu['prix'] = (float) $menu['prix_total'];
                $menu['prix_formate'] = number_format($menu['prix'], 0, ',', ' ') . ' FCFA';
                $menu['disponible'] = !$menu['est_archive'];
                $menu['description'] = 'Menu complet Brasil Burger';
            }

            $stats = $this->getStatsMenus($connection);

            return $this->render('admin/menu/list.html.twig', [
                'menus' => $menus,
                'totalMenus' => count($menus),
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur MenuController: " . $e->getMessage());
        }
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

    #[Route('/debug-simple', name: 'app_menu_debug_simple')]
    public function debugSimple(Connection $connection): Response
    {
        try {
            $countMenus = $connection->fetchOne("SELECT COUNT(*) FROM menu");
            $countActifs = $connection->fetchOne("SELECT COUNT(*) FROM menu WHERE est_archive = false OR est_archive IS NULL");
            $countProduits = $connection->fetchOne("SELECT COUNT(*) FROM produit");

            $menus = $connection->fetchAllAssociative("
                SELECT 
                    m.id, m.nom, m.est_archive,
                    b.nom as burger_nom, b.prix as burger_prix,
                    bo.nom as boisson_nom, bo.prix as boisson_prix,
                    f.nom as frite_nom, f.prix as frite_prix,
                    COALESCE(b.prix, 0) + COALESCE(bo.prix, 0) + COALESCE(f.prix, 0) as prix_total
                FROM menu m
                LEFT JOIN produit b ON m.burger_id = b.id
                LEFT JOIN produit bo ON m.boisson_id = bo.id  
                LEFT JOIN produit f ON m.frite_id = f.id
                LIMIT 5
            ");

            $html = "<h2>🎉 Debug Menus - Prix Calculés Automatiquement !</h2>";
            $html .= "<p><strong>Total menus:</strong> $countMenus</p>";
            $html .= "<p><strong>Menus actifs:</strong> $countActifs</p>";
            $html .= "<p><strong>Total produits:</strong> $countProduits</p>";
            
            $html .= "<h3>📋 Menus avec Prix Calculés :</h3>";
            $html .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            $html .= "<tr style='background: #f0f0f0;'>";
            $html .= "<th>Menu</th><th>Burger</th><th>Boisson</th><th>Frite</th><th>Prix Calculé</th><th>Statut</th>";
            $html .= "</tr>";
            
            foreach ($menus as $menu) {
                $archiveStatus = !$menu['est_archive'] ? '✅ Actif' : '❌ Archivé';
                $prixFormate = number_format($menu['prix_total'], 0, ',', ' ') . ' FCFA';
                
                $html .= "<tr>";
                $html .= "<td><strong>" . htmlspecialchars($menu['nom']) . "</strong></td>";
                $html .= "<td>" . ($menu['burger_nom'] ? htmlspecialchars($menu['burger_nom']) : "❌ NULL") . "</td>";
                $html .= "<td>" . ($menu['boisson_nom'] ? htmlspecialchars($menu['boisson_nom']) : "❌ NULL") . "</td>";
                $html .= "<td>" . ($menu['frite_nom'] ? htmlspecialchars($menu['frite_nom']) : "❌ NULL") . "</td>";
                $html .= "<td><strong style='color: green; font-size: 1.2em;'>" . $prixFormate . "</strong></td>";
                $html .= "<td>" . $archiveStatus . "</td>";
                $html .= "</tr>";
            }
            
            $html .= "</table>";
            $html .= "<hr>";
            $html .= "<p><a href='" . $this->generateUrl('app_menu_list') . "'>🚀 Voir l'Interface des Menus</a></p>";
            
            return new Response($html);

        } catch (\Exception $e) {
            return new Response("Erreur Debug: " . htmlspecialchars($e->getMessage()));
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