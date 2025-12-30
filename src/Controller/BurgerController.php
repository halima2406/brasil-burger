<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/burger')]
class BurgerController extends AbstractController
{
    #[Route('/list', name: 'app_burger_list')]
    public function list(Request $request, Connection $connection): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        
        $totalBurgers = $connection->fetchOne("
            SELECT COUNT(*) FROM produit p WHERE p.type_produit = 'BURGER'
        ") ?: 0;

        $burgers = $connection->fetchAllAssociative("
            SELECT p.id, p.nom, p.prix, p.type_produit,
                   COALESCE(SUM(lc.quantite), 0) as ventes_totales,
                   COALESCE(SUM(CASE WHEN DATE(c.date_commande) = CURRENT_DATE THEN lc.quantite ELSE 0 END), 0) as ventes_jour
            FROM produit p 
            LEFT JOIN ligne_commande lc ON p.id = lc.produit_id
            LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
            WHERE p.type_produit = 'BURGER'
            GROUP BY p.id, p.nom, p.prix, p.type_produit
            ORDER BY p.nom ASC
            LIMIT $perPage OFFSET $offset
        ");

        foreach ($burgers as &$burger) {
            $burger['prix_formate'] = number_format($burger['prix'], 0, ',', ' ');
            $burger['disponible'] = $burger['prix'] > 0;
            $burger['statut'] = $burger['disponible'] ? 'Actif' : 'Inactif';
            $burger['archive'] = false; 
        }

        
        $totalPages = ceil($totalBurgers / $perPage);
        $pagination = [
            'pageEnCours' => $page,
            'nbrePage' => $totalPages,
        ];

        return $this->render('admin/burger/list.html.twig', array_merge([
            'burgers' => $burgers
        ], $pagination));
    }

    #[Route('/details/{id}', name: 'app_burger_details')]
    public function details(int $id, Connection $connection): Response
    {
        try {
           
            $burger = $connection->fetchAssociative("
                SELECT p.id, p.nom, p.prix, p.type_produit,
                       COALESCE(SUM(lc.quantite), 0) as ventes_totales,
                       COALESCE(SUM(lc.montant_total), 0) as chiffre_affaires,
                       COALESCE(SUM(CASE WHEN DATE(c.date_commande) = CURRENT_DATE THEN lc.quantite ELSE 0 END), 0) as ventes_jour
                FROM produit p 
                LEFT JOIN ligne_commande lc ON p.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE p.id = ? AND p.type_produit = 'BURGER'
                GROUP BY p.id, p.nom, p.prix, p.type_produit
            ", [$id]);

            if (!$burger) {
                return $this->json(['error' => 'Burger non trouvé'], 404);
            }

      
            $commandes = $connection->fetchAllAssociative("
                SELECT c.date_commande, lc.quantite, lc.montant_total
                FROM ligne_commande lc
                JOIN commande c ON lc.commande_id = c.id
                WHERE lc.produit_id = ? AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                ORDER BY c.date_commande DESC
                LIMIT 10
            ", [$id]);

          
            $burger['prix_formate'] = number_format($burger['prix'], 0, ',', ' ');
            $burger['chiffre_affaires_formate'] = number_format($burger['chiffre_affaires'], 0, ',', ' ');
            $burger['disponible'] = $burger['prix'] > 0;

            foreach ($commandes as &$commande) {
                $commande['date_formate'] = date('d/m/Y H:i', strtotime($commande['date_commande']));
                $commande['total_formate'] = number_format($commande['montant_total'], 0, ',', ' ');
            }

            return $this->json([
                'success' => true,
                'burger' => $burger,
                'commandes' => $commandes
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors du chargement des détails'], 500);
        }
    }

    #[Route('/edit/{id}', name: 'app_burger_edit', methods: ['POST'])]
    public function edit(int $id, Request $request, Connection $connection): Response
    {
        try {
            $nom = $request->request->get('nom');
            $prix = $request->request->get('prix');

            if (empty($nom) || !is_numeric($prix) || $prix < 0) {
                return $this->json(['error' => 'Données invalides'], 400);
            }

            $burger = $connection->fetchAssociative("
                SELECT id FROM produit WHERE id = ? AND type_produit = 'BURGER'
            ", [$id]);

            if (!$burger) {
                return $this->json(['error' => 'Burger non trouvé'], 404);
            }

            $connection->executeStatement("
                UPDATE produit SET nom = ?, prix = ? WHERE id = ?
            ", [$nom, $prix, $id]);

            return $this->json([
                'success' => true,
                'message' => 'Burger modifié avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la modification'], 500);
        }
    }

    #[Route('/delete/{id}', name: 'app_burger_delete', methods: ['POST'])]
    public function delete(int $id, Connection $connection): Response
    {
        try {
            $burger = $connection->fetchAssociative("
                SELECT id, nom FROM produit WHERE id = ? AND type_produit = 'BURGER'
            ", [$id]);

            if (!$burger) {
                return $this->json(['error' => 'Burger non trouvé'], 404);
            }

            $commandesLiees = $connection->fetchOne("
                SELECT COUNT(*) FROM ligne_commande WHERE produit_id = ?
            ", [$id]);

            if ($commandesLiees > 0) {
                return $this->json(['error' => 'Impossible de supprimer : burger lié à des commandes'], 400);
            }

            $connection->executeStatement("DELETE FROM produit WHERE id = ?", [$id]);

            return $this->json([
                'success' => true,
                'message' => 'Burger supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la suppression'], 500);
        }
    }
}