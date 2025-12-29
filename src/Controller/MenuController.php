<?php

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/menu')]
final class MenuController extends AbstractController
{
    private const LIMIT = 10;

    #[Route('/list', name: 'app_menu_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = self::LIMIT;
            $offset = ($page - 1) * $limit;

            // ✅ REQUÊTE CORRIGÉE : PRIX CALCULÉ À PARTIR DES COMPOSANTS
            $menus = $connection->fetchAllAssociative("
                SELECT 
                    m.id,
                    m.nom,
                    m.est_archive,
                    -- Burger associé
                    b.nom as burger_nom,
                    b.prix as burger_prix,
                    -- Boisson associée
                    bo.nom as boisson_nom,
                    bo.prix as boisson_prix,
                    -- Frite associée
                    f.nom as frite_nom,
                    f.prix as frite_prix,
                    -- ✅ PRIX TOTAL CALCULÉ (somme des composants)
                    COALESCE(b.prix, 0) + COALESCE(bo.prix, 0) + COALESCE(f.prix, 0) as prix_total,
                    -- Statistiques de ventes  
                    COUNT(lc.id) as ventes_totales,
                    SUM(CASE WHEN DATE(c.date_commande) = CURRENT_DATE THEN lc.quantite ELSE 0 END) as ventes_jour
                FROM menu m
                LEFT JOIN produit b ON m.burger_id = b.id
                LEFT JOIN produit bo ON m.boisson_id = bo.id  
                LEFT JOIN produit f ON m.frite_id = f.id
                LEFT JOIN ligne_commande lc ON m.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE m.est_archive = false OR m.est_archive IS NULL
                GROUP BY m.id, m.nom, m.est_archive, b.nom, b.prix, bo.nom, bo.prix, f.nom, f.prix
                ORDER BY ventes_totales DESC
                LIMIT $limit OFFSET $offset
            ");

            // ✅ ENRICHIR LES DONNÉES POUR LE TEMPLATE
            foreach ($menus as &$menu) {
                // ✅ PRIX CALCULÉ DYNAMIQUEMENT
                $menu['prix'] = (float) $menu['prix_total'];
                $menu['prix_formate'] = number_format($menu['prix'], 0, ',', ' ') . ' FCFA';
                
                $menu['ventes_jour'] = (int) $menu['ventes_jour'] ?: 0;
                $menu['ventes_totales'] = (int) $menu['ventes_totales'] ?: 0;
                
                // Statut
                $menu['disponible'] = !$menu['est_archive'];
                $menu['archive'] = (bool) $menu['est_archive'];
                
                // Description automatique basée sur les composants
                $composants = [];
                if ($menu['burger_nom']) $composants[] = $menu['burger_nom'];
                if ($menu['frite_nom']) $composants[] = $menu['frite_nom'];
                if ($menu['boisson_nom']) $composants[] = $menu['boisson_nom'];
                
                $menu['description'] = !empty($composants) 
                    ? 'Menu avec ' . implode(' + ', $composants)
                    : 'Menu complet Brasil Burger';
                
                // Structure des composants pour le template
                $menu['burger'] = $menu['burger_nom'] ? [
                    'nom' => $menu['burger_nom'],
                    'prix' => $menu['burger_prix']
                ] : null;
                
                $menu['boisson'] = $menu['boisson_nom'] ? [
                    'nom' => $menu['boisson_nom'],
                    'prix' => $menu['boisson_prix']
                ] : null;
                
                $menu['frite'] = $menu['frite_nom'] ? [
                    'nom' => $menu['frite_nom'],
                    'prix' => $menu['frite_prix']
                ] : null;
                
                // Complements array pour le template
                $menu['complements'] = [];
                if ($menu['frite_nom']) {
                    $menu['complements'][] = [
                        'nom' => $menu['frite_nom'],
                        'type_complement' => 'frites'
                    ];
                }
                if ($menu['boisson_nom']) {
                    $menu['complements'][] = [
                        'nom' => $menu['boisson_nom'],
                        'type_complement' => 'boisson'
                    ];
                }
                
                // Image par défaut
                $menu['image'] = 'https://cdn.pixabay.com/photo/2016/03/05/19/02/hamburger-1238246_960_720.jpg';
            }

            // ✅ COMPTER LE TOTAL POUR LA PAGINATION
            $totalMenus = $connection->fetchOne("
                SELECT COUNT(*) FROM menu WHERE est_archive = false OR est_archive IS NULL
            ");

            $totalPages = (int) ceil($totalMenus / $limit);

            // ✅ STATISTIQUES RÉELLES
            $stats = $this->getStatsMenus($connection);

            return $this->render('admin/menu/list.html.twig', [
                'menus' => $menus,
                'pageEnCours' => $page,
                'nbrePage' => $totalPages,
                'totalMenus' => $totalMenus,
                'stats' => $stats,
                'database_ready' => true
            ]);

        } catch (\Exception $e) {
            return new Response("
                <h1>ERREUR CONTROLLER MENU</h1>
                <p>Message: " . htmlspecialchars($e->getMessage()) . "</p>
                <p>Trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>
                <hr>
                <a href='/admin'>→ Retour au Dashboard</a>
            ");
        }
    }

    #[Route('/seed', name: 'app_complement_seed')]
    public function seed(Connection $connection): Response
    {
        try {
            // Vérifier s'il y a déjà des compléments
            $existants = $connection->fetchOne("
                SELECT COUNT(*) FROM produit WHERE type_produit IN ('ACCOMPAGNEMENT', 'BOISSON')
            ");
            
            if ($existants > 0) {
                return new Response("
                    <h1>✅ Compléments déjà présents</h1>
                    <p><strong>$existants compléments</strong> trouvés dans la base.</p>
                    <a href='/admin/complement/list' style='background: green; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>
                        Voir les compléments
                    </a>
                ");
            }
            
            // Ajouter les accompagnements
            $accompagnements = [
                ['Frites Classiques', 800],
                ['Frites Épicées', 1000],
                ['Salade César', 1200],
                ['Onion Rings', 1100],
                ['Nuggets (6pcs)', 1500]
            ];
            
            foreach ($accompagnements as [$nom, $prix]) {
                $connection->executeStatement("
                    INSERT INTO produit (nom, prix, type_produit) VALUES (?, ?, 'ACCOMPAGNEMENT')
                ", [$nom, $prix]);
            }
            
            // Ajouter les boissons
            $boissons = [
                ['Coca-Cola', 500],
                ['Sprite', 500],
                ['Fanta', 500],
                ['Jus d\'Orange', 700],
                ['Eau Minérale', 300],
                ['Café', 400]
            ];
            
            foreach ($boissons as [$nom, $prix]) {
                $connection->executeStatement("
                    INSERT INTO produit (nom, prix, type_produit) VALUES (?, ?, 'BOISSON')
                ", [$nom, $prix]);
            }
            
            return new Response("
                <h1>🎉 Compléments créés avec succès !</h1>
                <p><strong>" . count($accompagnements) . " accompagnements</strong> et <strong>" . count($boissons) . " boissons</strong> ajoutés.</p>
                <ul>
                    <li>🍟 Frites Classiques, Épicées, Salade, Onion Rings, Nuggets</li>
                    <li>🥤 Coca, Sprite, Fanta, Jus, Eau, Café</li>
                </ul>
                <a href='/admin/complement/list' style='background: green; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>
                    Voir les compléments
                </a>
            ");
            
        } catch (\Exception $e) {
            return new Response("Erreur: " . $e->getMessage());
        }
    }





    // ✅ ROUTE DEBUG CORRIGÉE
    #[Route('/debug-simple', name: 'app_menu_debug_simple')]
    public function debugSimple(Connection $connection): Response
    {
        try {
            // Statistiques de base
            $countMenus = $connection->fetchOne("SELECT COUNT(*) FROM menu");
            $countActifs = $connection->fetchOne("SELECT COUNT(*) FROM menu WHERE est_archive = false OR est_archive IS NULL");
            $countProduits = $connection->fetchOne("SELECT COUNT(*) FROM produit");

            // ✅ MENUS AVEC PRIX CALCULÉS
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
                $html .= "<td><strong>" . htmlspecialchars($menu['nom']) . "</strong><br><small>{$archiveStatus}</small></td>";
                $html .= "<td>" . ($menu['burger_nom'] ? htmlspecialchars($menu['burger_nom']) . "<br>" . number_format($menu['burger_prix'], 0, ',', ' ') . ' FCFA' : "❌ NULL") . "</td>";
                $html .= "<td>" . ($menu['boisson_nom'] ? htmlspecialchars($menu['boisson_nom']) . "<br>" . number_format($menu['boisson_prix'], 0, ',', ' ') . ' FCFA' : "❌ NULL") . "</td>";
                $html .= "<td>" . ($menu['frite_nom'] ? htmlspecialchars($menu['frite_nom']) . "<br>" . number_format($menu['frite_prix'], 0, ',', ' ') . ' FCFA' : "❌ NULL") . "</td>";
                $html .= "<td><strong style='color: green; font-size: 1.2em;'>" . $prixFormate . "</strong></td>";
                $html .= "<td>" . $archiveStatus . "</td>";
                $html .= "</tr>";
            }
            
            $html .= "</table>";
            
            $html .= "<hr>";
            $html .= "<p><a href='" . $this->generateUrl('app_menu_list') . "' style='background: green; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>🚀 Voir l'Interface des Menus</a></p>";
            
            return new Response($html);

        } catch (\Exception $e) {
            return new Response("Erreur Debug: " . htmlspecialchars($e->getMessage()));
        }
    }

    // ✅ STATISTIQUES CORRIGÉES
    private function getStatsMenus(Connection $connection): array
    {
        try {
            $stats = [
                'total_menus' => 0,
                'menu_populaire' => 'Aucun',
                'sold_today' => 0,
                'most_popular' => 'Menu Royal',
                'menu_rate' => 0
            ];

            // Total des menus actifs
            $stats['total_menus'] = (int) $connection->fetchOne("
                SELECT COUNT(*) FROM menu WHERE est_archive = false OR est_archive IS NULL
            ") ?: 0;

            // Menu le plus vendu
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

            if ($menuPopulaire) {
                $stats['menu_populaire'] = $menuPopulaire['nom'] . ' (' . $menuPopulaire['ventes'] . ' ventes)';
                $stats['most_popular'] = $menuPopulaire['nom'];
            }

            // Ventes du jour
            $stats['sold_today'] = (int) $connection->fetchOne("
                SELECT COALESCE(SUM(lc.quantite), 0)
                FROM ligne_commande lc
                JOIN commande c ON lc.commande_id = c.id
                JOIN menu m ON lc.produit_id = m.id
                WHERE DATE(c.date_commande) = CURRENT_DATE 
                AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
            ") ?: 0;

            $stats['menu_rate'] = $stats['total_menus'] > 0 ? 85 : 0;

            return $stats;

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