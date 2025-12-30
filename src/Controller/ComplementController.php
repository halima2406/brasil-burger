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
                    p.type_complement,
                    COUNT(lc.id) as ventes_totales
                FROM produit p
                LEFT JOIN ligne_commande lc ON p.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE $whereCondition
                GROUP BY p.id, p.nom, p.prix, p.type_produit, p.type_complement
                ORDER BY p.nom ASC
                LIMIT 10
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

            return $this->render('admin/complement/list.html.twig', [
                'complements' => $complements,
                'filter' => $filter,
                'stats' => $stats,
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

    private function getStatsComplements(Connection $connection): array
    {
        try {
            $totalComplements = $connection->fetchOne("
                SELECT COUNT(*) FROM produit p
                WHERE p.type_complement IN ('FRITE', 'BOISSON') OR p.type_produit = 'COMPLEMENT'
            ") ?: 0;

            $accompagnements = $connection->fetchOne("
                SELECT COUNT(*) FROM produit p WHERE p.type_complement = 'FRITE'
            ") ?: 0;

            $boissons = $connection->fetchOne("
                SELECT COUNT(*) FROM produit p WHERE p.type_complement = 'BOISSON'
            ") ?: 0;

            $complementPopulaire = $connection->fetchAssociative("
                SELECT p.nom, COUNT(lc.id) as ventes
                FROM produit p
                LEFT JOIN ligne_commande lc ON p.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE p.type_complement IN ('FRITE', 'BOISSON') OR p.type_produit = 'COMPLEMENT'
                GROUP BY p.id, p.nom
                ORDER BY ventes DESC
                LIMIT 1
            ");

            return [
                'total_complements' => (int) $totalComplements,
                'accompagnements' => (int) $accompagnements,
                'boissons' => (int) $boissons,
                'complement_populaire' => $complementPopulaire ? $complementPopulaire['nom'] : 'Aucun'
            ];

        } catch (\Exception $e) {
            return [
                'total_complements' => 0,
                'accompagnements' => 0,
                'boissons' => 0,
                'complement_populaire' => 'Erreur'
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

    private function getDescription(string $nom, string $type): string
    {
        switch ($type) {
            case 'BOISSON':
                return 'Boisson rafraîchissante pour accompagner votre repas';
            case 'FRITE':
                return stripos($nom, 'épicée') !== false ? 'Frites relevées aux épices du chef' : 'Délicieuses frites dorées et croustillantes';
            default:
                return 'Accompagnement savoureux pour vos burgers';
        }
    }

    private function getImageUrl(string $nom, string $type): string
    {
        switch ($type) {
            case 'BOISSON':
                if (stripos($nom, 'coca') !== false) {
                    return 'https://images.unsplash.com/photo-1546171753-97d7676e4602?w=100&h=100&fit=crop';
                } elseif (stripos($nom, 'sprite') !== false) {
                    return 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=100&h=100&fit=crop';
                } else {
                    return 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=100&h=100&fit=crop';
                }
            case 'FRITE':
                return 'https://images.unsplash.com/photo-1576107232684-1279f390859f?w=100&h=100&fit=crop';
            default:
                return 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=100&h=100&fit=crop';
        }
    }
}