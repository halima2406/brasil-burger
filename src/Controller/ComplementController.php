<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/complement')]
class ComplementController extends AbstractController
{
    #[Route('/list', name: 'app_complement_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
            $filter = $request->query->get('filter', 'tous');
            
            $whereCondition = $this->getWhereConditionForFilter($filter);
            
            $complements = $connection->fetchAllAssociative("
                SELECT 
                    p.id,
                    p.nom,
                    p.prix,
                    p.type_produit,
                    p.type_complement
                FROM produit p
                WHERE $whereCondition
                ORDER BY p.nom ASC
                LIMIT 10
            ");

            foreach ($complements as &$complement) {
                $complement['prix_formate'] = number_format($complement['prix'], 0, ',', ' ') . ' FCFA';
                $complement['disponible'] = $complement['prix'] > 0;
                $complement['description'] = 'Complément savoureux Brasil Burger';
            }

            return $this->render('admin/complement/list.html.twig', [
                'complements' => $complements,
                'filter' => $filter,
                'totalComplements' => count($complements)
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur ComplementController: " . $e->getMessage());
        }
    }

    #[Route('/details/{id}', name: 'app_complement_details', methods: ['GET'])]
    public function details(int $id, Connection $connection): JsonResponse
    {
        try {
            $complement = $connection->fetchAssociative("
                SELECT 
                    p.id, p.nom, p.prix, p.type_produit, p.type_complement,
                    COUNT(lc.id) as ventes_totales,
                    SUM(CASE WHEN DATE(c.date_commande) = CURRENT_DATE THEN lc.quantite ELSE 0 END) as ventes_jour,
                    SUM(lc.prix_unitaire * lc.quantite) as chiffre_affaires_total
                FROM produit p
                LEFT JOIN ligne_commande lc ON p.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE p.id = ?
                GROUP BY p.id, p.nom, p.prix, p.type_produit, p.type_complement
            ", [$id]);

            if (!$complement) {
                return $this->json(['error' => 'Complément non trouvé'], 404);
            }

            $commandesRecentes = $connection->fetchAllAssociative("
                SELECT c.id, c.date_commande, lc.quantite, lc.prix_unitaire, c.statut
                FROM ligne_commande lc
                JOIN commande c ON lc.commande_id = c.id
                WHERE lc.produit_id = ? AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                ORDER BY c.date_commande DESC
                LIMIT 10
            ", [$id]);

            $complement['prix_formate'] = number_format($complement['prix'], 0, ',', ' ') . ' FCFA';
            $complement['chiffre_affaires_formate'] = number_format($complement['chiffre_affaires_total'], 0, ',', ' ') . ' FCFA';
            $complement['ventes_jour'] = (int) $complement['ventes_jour'];
            $complement['ventes_totales'] = (int) $complement['ventes_totales'];

            foreach ($commandesRecentes as &$commande) {
                $commande['date_formate'] = date('d/m/Y H:i', strtotime($commande['date_commande']));
                $commande['total_formate'] = number_format($commande['quantite'] * $commande['prix_unitaire'], 0, ',', ' ') . ' FCFA';
            }

            return $this->json([
                'complement' => $complement,
                'commandes' => $commandesRecentes
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/edit/{id}', name: 'app_complement_edit', methods: ['POST'])]
    public function edit(int $id, Request $request, Connection $connection): JsonResponse
    {
        try {
            $nom = trim($request->request->get('nom'));
            $prix = (float) $request->request->get('prix');

            if (empty($nom)) {
                return $this->json(['error' => 'Le nom est requis'], 400);
            }

            if ($prix < 0) {
                return $this->json(['error' => 'Le prix doit être positif'], 400);
            }

            $complement = $connection->fetchAssociative("
                SELECT id FROM produit WHERE id = ?
            ", [$id]);

            if (!$complement) {
                return $this->json(['error' => 'Complément non trouvé'], 404);
            }

            $connection->executeStatement("
                UPDATE produit SET nom = ?, prix = ? WHERE id = ?
            ", [$nom, $prix, $id]);

            return $this->json([
                'success' => true,
                'message' => 'Complément modifié avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/delete/{id}', name: 'app_complement_delete', methods: ['POST'])]
    public function delete(int $id, Connection $connection): JsonResponse
    {
        try {
            $complement = $connection->fetchAssociative("
                SELECT id, nom FROM produit WHERE id = ?
            ", [$id]);

            if (!$complement) {
                return $this->json(['error' => 'Complément non trouvé'], 404);
            }

            $commandesLiees = $connection->fetchOne("
                SELECT COUNT(*) FROM ligne_commande WHERE produit_id = ?
            ", [$id]);

            if ($commandesLiees > 0) {
                return $this->json(['error' => 'Impossible de supprimer : complément lié à des commandes'], 400);
            }

            $connection->executeStatement("DELETE FROM produit WHERE id = ?", [$id]);

            return $this->json([
                'success' => true,
                'message' => 'Complément supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getWhereConditionForFilter(string $filter): string
    {
        switch ($filter) {
            case 'frites':
                return "p.type_complement = 'FRITE'";
            case 'boissons':
                return "p.type_complement = 'BOISSON'";
            case 'complements':
                return "p.type_produit = 'COMPLEMENT'";
            case 'tous':
            default:
                return "p.type_complement IN ('FRITE', 'BOISSON') OR p.type_produit = 'COMPLEMENT'";
        }
    }
}