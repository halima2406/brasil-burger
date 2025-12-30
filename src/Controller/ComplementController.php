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

    // [Méthodes details, edit, delete restent identiques...]

    public function getWhereConditionForFilter(string $filter): string
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

    public function getStatsComplements(Connection $connection): array
    {
        try {
            $totalComplements = $connection->fetchOne("
                SELECT COUNT(*) FROM produit p
                WHERE p.type_complement IN ('FRITE', 'BOISSON') OR p.type_produit = 'COMPLEMENT'
            ") ?: 0;

            return [
                'total_complements' => (int) $totalComplements,
                'accompagnements' => 0,
                'boissons' => 0,
                'complement_populaire' => 'Aucun'
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
        return 'https://images.unsplash.com/photo-1565299624946-b28f40a0ca4b?w=100&h=100&fit=crop';
    }
}