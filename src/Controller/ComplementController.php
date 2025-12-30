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
                return 'Boisson rafraîchissante pour accompagner votre repas';
            case 'FRITE':
                if (stripos($nom, 'épicée') !== false || stripos($nom, 'epicee') !== false) {
                    return 'Frites relevées aux épices du chef';
                } elseif (stripos($nom, 'bacon') !== false && stripos($nom, 'cheese') !== false) {
                    return 'Frites garnies de bacon et fromage fondu';
                } else {
                    return 'Délicieuses frites dorées et croustillantes';
                }
            case 'COMPLEMENT':
                if (stripos($nom, 'salade') !== false) {
                    return 'Salade fraîche et équilibrée';
                } elseif (stripos($nom, 'nugget') !== false) {
                    return 'Nuggets de poulet dorés et croustillants';
                } else {
                    return 'Accompagnement savoureux pour vos burgers';
                }
            default:
                return 'Accompagnement savoureux pour vos burgers';
        }
    }

    public function getImageUrl(string $nom, string $type): string
    {
        switch ($type) {
            case 'BOISSON':
                if (stripos($nom, 'coca') !== false) {
                    return 'https://images.unsplash.com/photo-1546171753-97d7676e4602?w=100&h=100&fit=crop&q=80';
                } elseif (stripos($nom, 'fanta') !== false) {
                    return 'https://images.unsplash.com/photo-1624552185007-020e4203ac8d?w=100&h=100&fit=crop&q=80';
                } elseif (stripos($nom, 'sprite') !== false) {
                    return 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=100&h=100&fit=crop&q=80';
                } elseif (stripos($nom, 'jus') !== false || stripos($nom, 'orange') !== false) {
                    return 'https://images.unsplash.com/photo-1613478223719-2ab802602423?w=100&h=100&fit=crop&q=80';
                } elseif (stripos($nom, 'eau') !== false) {
                    return 'https://images.unsplash.com/photo-1523362628745-0c100150b504?w=100&h=100&fit=crop&q=80';
                } elseif (stripos($nom, 'café') !== false || stripos($nom, 'cafe') !== false) {
                    return 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=100&h=100&fit=crop&q=80';
                } else {
                    return 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=100&h=100&fit=crop&q=80';
                }
                
            case 'FRITE':
                if (stripos($nom, 'bacon') !== false && stripos($nom, 'cheese') !== false) {
                    return 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=100&h=100&fit=crop&q=80';
                } elseif (stripos($nom, 'épicée') !== false || stripos($nom, 'epicee') !== false) {
                    return 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=100&h=100&fit=crop&q=80';
                } else {
                    return 'https://images.unsplash.com/photo-1576107232684-1279f390859f?w=100&h=100&fit=crop&q=80';
                }
                
            case 'COMPLEMENT':
                if (stripos($nom, 'salade') !== false) {
                    return 'https://images.unsplash.com/photo-1546793665-c74683f339c1?w=100&h=100&fit=crop&q=80';
                } elseif (stripos($nom, 'nugget') !== false) {
                    return 'https://images.unsplash.com/photo-1562967914-608f82629710?w=100&h=100&fit=crop&q=80';
                } else {
                    return 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=100&h=100&fit=crop&q=80';
                }
                
            default:
                return 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=100&h=100&fit=crop&q=80';
        }
    }
}