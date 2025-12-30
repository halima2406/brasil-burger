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
            $page = max(1, (int) $request->query->get('page', 1));
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            
            $whereCondition = $this->getWhereConditionForFilter($filter);
            
            // Compter le total pour la pagination
            $totalItems = $connection->fetchOne("
                SELECT COUNT(*) FROM produit p
                WHERE $whereCondition
            ") ?: 0;
            
            $complements = $connection->fetchAllAssociative("
                SELECT 
                    p.id,
                    p.nom,
                    p.prix,
                    p.type_produit,
                    p.type_complement,
                    COUNT(lc.id) as ventes_totales
                FROM produit p
                LEFT JOIN ligne_commande lc ON p.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE $whereCondition
                GROUP BY p.id, p.nom, p.prix, p.type_produit, p.type_complement
                ORDER BY p.nom ASC
                LIMIT $perPage OFFSET $offset
            ");

            foreach ($complements as &$complement) {
                $complement['prix_formate'] = number_format($complement['prix'], 0, ',', ' ') . ' FCFA';
                $complement['disponible'] = $complement['prix'] > 0;
                $complement['description'] = $this->getDescription($complement['nom'], $complement['type_complement'] ?: $complement['type_produit']);
                $complement['image_url'] = $this->getImageUrl($complement['nom'], $complement['type_complement'] ?: $complement['type_produit']);
                $complement['popularite'] = $this->getPopularite((int) $complement['ventes_totales']);
                
                $typeAffichage = $complement['type_complement'] ?: $complement['type_produit'];
                switch ($typeAffichage) {
                    case 'FRITE':
                        $complement['categorie'] = 'frite';
                        break;
                    case 'BOISSON':
                        $complement['categorie'] = 'boisson';
                        break;
                    default:
                        $complement['categorie'] = 'accompagnement';
                }
            }

            $stats = $this->getStatsComplements($connection);
            
            // Calculs pagination
            $totalPages = ceil($totalItems / $perPage);
            $pagination = [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'per_page' => $perPage,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'previous_page' => $page > 1 ? $page - 1 : null,
                'next_page' => $page < $totalPages ? $page + 1 : null
            ];

            return $this->render('admin/complement/list.html.twig', [
                'complements' => $complements,
                'filter' => $filter,
                'stats' => $stats,
                'totalComplements' => $totalItems,
                'pagination' => $pagination
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
                SELECT p.*, COUNT(lc.id) as ventes_totales
                FROM produit p
                LEFT JOIN ligne_commande lc ON p.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE p.id = ? AND (p.type_complement IN ('FRITE', 'BOISSON') OR p.type_produit = 'COMPLEMENT')
                GROUP BY p.id
            ", [$id]);

            if (!$complement) {
                return $this->json(['error' => 'Complément non trouvé'], 404);
            }

            $complement['prix_formate'] = number_format($complement['prix'], 0, ',', ' ') . ' FCFA';
            $complement['disponible'] = $complement['prix'] > 0;
            $complement['description'] = $this->getDescription($complement['nom'], $complement['type_complement'] ?: $complement['type_produit']);
            $complement['image_url'] = $this->getImageUrl($complement['nom'], $complement['type_complement'] ?: $complement['type_produit']);
            $complement['popularite'] = $this->getPopularite((int) $complement['ventes_totales']);

            return $this->json([
                'success' => true,
                'complement' => $complement
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/edit/{id}', name: 'app_complement_edit', methods: ['POST'])]
    public function edit(int $id, Request $request, Connection $connection): JsonResponse
    {
        try {
            $nom = $request->request->get('nom');
            $prix = $request->request->get('prix');

            if (empty($nom) || !is_numeric($prix) || $prix < 0) {
                return $this->json(['error' => 'Données invalides'], 400);
            }

            $complement = $connection->fetchAssociative("
                SELECT id FROM produit 
                WHERE id = ? AND (type_complement IN ('FRITE', 'BOISSON') OR type_produit = 'COMPLEMENT')
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
                SELECT id, nom FROM produit 
                WHERE id = ? AND (type_complement IN ('FRITE', 'BOISSON') OR type_produit = 'COMPLEMENT')
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

    public function getWhereConditionForFilter(string $filter): string
    {
        switch ($filter) {
            case 'frites':
                return "p.type_complement = 'FRITE' OR (p.type_produit = 'ACCOMPAGNEMENT' AND p.nom LIKE '%Frite%')";
            case 'boissons':
                return "p.type_complement = 'BOISSON' OR p.type_produit = 'BOISSON'";
            case 'complements':
                return "p.type_produit = 'COMPLEMENT'";
            case 'tous':
            default:
                return "p.type_complement IN ('FRITE', 'BOISSON') OR p.type_produit IN ('COMPLEMENT', 'BOISSON', 'ACCOMPAGNEMENT')";
        }
    }

    public function getStatsComplements(Connection $connection): array
    {
        try {
            $totalComplements = $connection->fetchOne("
                SELECT COUNT(*) FROM produit p
                WHERE p.type_complement IN ('FRITE', 'BOISSON') OR p.type_produit IN ('COMPLEMENT', 'BOISSON', 'ACCOMPAGNEMENT')
            ") ?: 0;

            $nbFrites = $connection->fetchOne("
                SELECT COUNT(*) FROM produit p
                WHERE p.type_complement = 'FRITE' OR (p.type_produit = 'ACCOMPAGNEMENT' AND p.nom LIKE '%Frite%')
            ") ?: 0;

            $nbBoissons = $connection->fetchOne("
                SELECT COUNT(*) FROM produit p  
                WHERE p.type_complement = 'BOISSON' OR p.type_produit = 'BOISSON'
            ") ?: 0;

            $complementPopulaire = $connection->fetchAssociative("
                SELECT p.nom, COUNT(lc.id) as ventes
                FROM produit p
                LEFT JOIN ligne_commande lc ON p.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE p.type_complement IN ('FRITE', 'BOISSON') OR p.type_produit IN ('COMPLEMENT', 'BOISSON', 'ACCOMPAGNEMENT')
                GROUP BY p.id, p.nom
                ORDER BY ventes DESC
                LIMIT 1
            ");

            return [
                'total_complements' => (int) $totalComplements,
                'frites' => (int) $nbFrites,
                'boissons' => (int) $nbBoissons,
                'complement_populaire' => $complementPopulaire ? $complementPopulaire['nom'] : 'Aucun'
            ];

        } catch (\Exception $e) {
            return [
                'total_complements' => 0,
                'frites' => 0,
                'boissons' => 0,
                'complement_populaire' => 'Erreur'
            ];
        }
    }

    public function getPopularite(int $ventes): string
    {
        if ($ventes >= 20) return 'Populaire';
        if ($ventes > 0) return 'Modéré';
        return 'Peu vendu';
    }

    public function getDescription(string $nom, string $type): string
    {
        switch ($type) {
            case 'BOISSON':
                return 'Boisson rafraîchissante';
            case 'FRITE':
                return 'Frites croustillantes';
            default:
                return 'Accompagnement savoureux';
        }
    }

    public function getImageUrl(string $nom, string $type): string
    {
       
        $seed = crc32(strtolower($nom));
        
        switch ($type) {
            case 'BOISSON':
                return "https://picsum.photos/seed/{$seed}/100/100"; 
            case 'FRITE':
                return "https://picsum.photos/seed/{$seed}/100/100";
            default:
                return "https://picsum.photos/seed/{$seed}/100/100";
        }
    }
}