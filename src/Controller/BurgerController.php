<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/burger')]
final class BurgerController extends AbstractController
{
    private const LIMIT = 5;

    #[Route('/list', name: 'app_burger_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = self::LIMIT;
            $offset = ($page - 1) * $limit;

            // ✅ REQUÊTE SQL CORRIGÉE
            $burgers = $connection->fetchAllAssociative("
                SELECT 
                    p.id,
                    p.nom,
                    p.prix,
                    p.type_produit,
                    COUNT(lc.id) as ventes_totales,
                    SUM(CASE WHEN DATE(c.date_commande) = CURRENT_DATE THEN lc.quantite ELSE 0 END) as ventes_jour,
                    SUM(lc.prix_unitaire * lc.quantite) as chiffre_affaires_total
                FROM produit p
                LEFT JOIN ligne_commande lc ON p.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE p.type_produit = 'BURGER'
                GROUP BY p.id, p.nom, p.prix, p.type_produit
                ORDER BY ventes_totales DESC
                LIMIT $limit OFFSET $offset
            ");

            $totalBurgers = $connection->fetchOne("
                SELECT COUNT(*) FROM produit WHERE type_produit = 'BURGER'
            ");

            // ✅ ENRICHIR AVEC TOUTES LES PROPRIÉTÉS ATTENDUES
            foreach ($burgers as &$burger) {
                $burger['ventes_jour'] = (int) $burger['ventes_jour'] ?: 0;
                $burger['ventes_totales'] = (int) $burger['ventes_totales'] ?: 0;
                $burger['chiffre_affaires'] = (float) $burger['chiffre_affaires_total'] ?: 0;
                
                $burger['prix_formate'] = number_format($burger['prix'], 0, ',', ' ');
                $burger['chiffre_affaires_formate'] = number_format($burger['chiffre_affaires'], 0, ',', ' ');
                
                $burger['description'] = 'Burger savoureux avec ingrédients frais';
                $burger['disponible'] = $burger['prix'] > 0;
                $burger['statut'] = $burger['disponible'] ? 'Actif' : 'Inactif';
                $burger['popularite'] = $this->getPopularite($burger['ventes_totales']);
                $burger['archive'] = false; // Par défaut, aucun burger archivé
            }

            $totalPages = (int) ceil($totalBurgers / $limit);

            return $this->render('admin/burger/list.html.twig', [
                'burgers' => $burgers,
                'pageEnCours' => $page,
                'nbrePage' => $totalPages,
                'totalBurgers' => $totalBurgers,
                'stats' => $this->getStatsBurgers($connection)
            ]);

        } catch (\Exception $e) {
            return new Response("
                <h1>ERREUR CONTROLLER BURGER</h1>
                <p>Message: " . $e->getMessage() . "</p>
                <a href='/admin'>→ Retour au Dashboard</a>
            ");
        }
    }

    // ✅ NOUVELLE ROUTE : Voir détails d'un burger
    #[Route('/details/{id}', name: 'app_burger_details', methods: ['GET'])]
    public function details(int $id, Connection $connection): Response
    {
        try {
            // Récupérer le burger avec ses statistiques
            $burger = $connection->fetchAssociative("
                SELECT 
                    p.id,
                    p.nom,
                    p.prix,
                    p.type_produit,
                    COUNT(lc.id) as ventes_totales,
                    SUM(CASE WHEN DATE(c.date_commande) = CURRENT_DATE THEN lc.quantite ELSE 0 END) as ventes_jour,
                    SUM(lc.prix_unitaire * lc.quantite) as chiffre_affaires_total
                FROM produit p
                LEFT JOIN ligne_commande lc ON p.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE p.id = ? AND p.type_produit = 'BURGER'
                GROUP BY p.id, p.nom, p.prix, p.type_produit
            ", [$id]);

            if (!$burger) {
                return $this->json(['error' => 'Burger non trouvé'], 404);
            }

            // Récupérer les commandes récentes de ce burger
            $commandesRecentes = $connection->fetchAllAssociative("
                SELECT c.id, c.date_commande, lc.quantite, lc.prix_unitaire, c.statut
                FROM ligne_commande lc
                JOIN commande c ON lc.commande_id = c.id
                WHERE lc.produit_id = ? AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                ORDER BY c.date_commande DESC
                LIMIT 10
            ", [$id]);

            // Formatage
            $burger['prix_formate'] = number_format($burger['prix'], 0, ',', ' ');
            $burger['chiffre_affaires_formate'] = number_format($burger['chiffre_affaires_total'], 0, ',', ' ');
            $burger['ventes_jour'] = (int) $burger['ventes_jour'];
            $burger['ventes_totales'] = (int) $burger['ventes_totales'];

            foreach ($commandesRecentes as &$commande) {
                $commande['date_formate'] = date('d/m/Y H:i', strtotime($commande['date_commande']));
                $commande['total_formate'] = number_format($commande['quantite'] * $commande['prix_unitaire'], 0, ',', ' ');
            }

            return $this->json([
                'burger' => $burger,
                'commandes' => $commandesRecentes
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // ✅ NOUVELLE ROUTE : Éditer le prix d'un burger
    #[Route('/edit/{id}', name: 'app_burger_edit', methods: ['POST'])]
    public function edit(int $id, Request $request, Connection $connection): Response
    {
        try {
            $nouveauPrix = $request->request->get('prix');
            $nouveauNom = $request->request->get('nom');

            // Validation
            if (!is_numeric($nouveauPrix) || $nouveauPrix < 0) {
                return $this->json(['error' => 'Prix invalide'], 400);
            }

            if (empty($nouveauNom)) {
                return $this->json(['error' => 'Nom requis'], 400);
            }

            // Vérifier que le burger existe
            $burger = $connection->fetchAssociative("
                SELECT id, nom, prix FROM produit WHERE id = ? AND type_produit = 'BURGER'
            ", [$id]);

            if (!$burger) {
                return $this->json(['error' => 'Burger non trouvé'], 404);
            }

            // Mettre à jour
            $connection->executeStatement("
                UPDATE produit 
                SET nom = ?, prix = ? 
                WHERE id = ?
            ", [$nouveauNom, $nouveauPrix, $id]);

            return $this->json([
                'success' => true,
                'message' => 'Burger mis à jour avec succès',
                'burger' => [
                    'nom' => $nouveauNom,
                    'prix' => $nouveauPrix,
                    'prix_formate' => number_format($nouveauPrix, 0, ',', ' ')
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // ✅ NOUVELLE ROUTE : Changer statut (actif/inactif)
    #[Route('/toggle/{id}', name: 'app_burger_toggle', methods: ['POST'])]
    public function toggle(int $id, Connection $connection): Response
    {
        try {
            // Récupérer le burger
            $burger = $connection->fetchAssociative("
                SELECT id, nom, prix FROM produit WHERE id = ? AND type_produit = 'BURGER'
            ", [$id]);

            if (!$burger) {
                return $this->json(['error' => 'Burger non trouvé'], 404);
            }

            // Toggle la disponibilité (prix = 0 → inactif)
            $nouveauPrix = $burger['prix'] > 0 ? 0 : 2500; // Remettre un prix par défaut

            $connection->executeStatement("
                UPDATE produit SET prix = ? WHERE id = ?
            ", [$nouveauPrix, $id]);

            return $this->json([
                'success' => true,
                'message' => $nouveauPrix > 0 ? 'Burger activé' : 'Burger désactivé',
                'nouveau_statut' => $nouveauPrix > 0 ? 'actif' : 'inactif'
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // ✅ NOUVELLE ROUTE : Supprimer (marquer comme supprimé)
    #[Route('/delete/{id}', name: 'app_burger_delete', methods: ['POST'])]
    public function delete(int $id, Connection $connection): Response
    {
        try {
            // Vérifier que le burger existe
            $burger = $connection->fetchAssociative("
                SELECT id, nom FROM produit WHERE id = ? AND type_produit = 'BURGER'
            ", [$id]);

            if (!$burger) {
                return $this->json(['error' => 'Burger non trouvé'], 404);
            }

            // Vérifier s'il y a des commandes liées
            $commandesLiees = $connection->fetchOne("
                SELECT COUNT(*) FROM ligne_commande WHERE produit_id = ?
            ", [$id]);

            if ($commandesLiees > 0) {
                return $this->json(['error' => 'Impossible de supprimer : burger lié à des commandes'], 400);
            }

            // Supprimer définitivement si aucune commande
            $connection->executeStatement("DELETE FROM produit WHERE id = ?", [$id]);

            return $this->json([
                'success' => true,
                'message' => 'Burger supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getStatsBurgers(Connection $connection): array
    {
        try {
            $stats = [
                'total_burgers' => (int) $connection->fetchOne("SELECT COUNT(*) FROM produit WHERE type_produit = 'BURGER'") ?: 0,
                'burgers_vendus' => 0,
                'burger_populaire' => 'Aucun',
                'recettes_burgers' => 0,
                'recettes_burgers_formate' => '0'
            ];

            $burgerPopulaire = $connection->fetchAssociative("
                SELECT p.nom, COUNT(lc.id) as ventes
                FROM produit p
                JOIN ligne_commande lc ON p.id = lc.produit_id
                JOIN commande c ON lc.commande_id = c.id
                WHERE p.type_produit = 'BURGER' 
                AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                GROUP BY p.id, p.nom
                ORDER BY ventes DESC
                LIMIT 1
            ");

            if ($burgerPopulaire) {
                $stats['burger_populaire'] = $burgerPopulaire['nom'] . ' (' . $burgerPopulaire['ventes'] . ' ventes)';
            }

            return $stats;

        } catch (\Exception $e) {
            return [
                'total_burgers' => 0,
                'burgers_vendus' => 0,
                'burger_populaire' => 'Aucun',
                'recettes_burgers' => 0,
                'recettes_burgers_formate' => '0'
            ];
        }
    }

    private function getPopularite(int $ventes): string
    {
        if ($ventes >= 50) return 'Très populaire';
        if ($ventes >= 20) return 'Populaire';
        if ($ventes >= 5) return 'Modéré';
        if ($ventes > 0) return 'Peu vendu';
        return 'Jamais vendu';
    }

    #[Route('/add', name: 'app_burger_add')]
    public function add(): Response
    {
        return new Response("
            <h1>Ajouter un Burger</h1>
            <p>Fonctionnalité à implémenter</p>
            <a href='/admin/burger/list'>← Retour à la liste</a>
        ");
    }
}