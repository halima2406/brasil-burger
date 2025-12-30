<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/livraison')]
class LivraisonController extends AbstractController
{
    private const LIMIT = 10;

    #[Route('/list', name: 'app_livraison_list')]
    public function list(Connection $connection): Response
    {
        try {
            $commandesEnAttente = $connection->fetchAllAssociative("
                SELECT 
                    c.id,
                    'CMD' || c.id as numero_commande,
                    c.date_commande,
                    c.montant_total as total,
                    'Client' as client_nom
                FROM commande c
                WHERE c.statut IN ('VALIDEE', 'EN_COURS') 
                AND c.livreur_id IS NULL
                ORDER BY c.date_commande ASC
                LIMIT 5
            ");

            $livreurs = $connection->fetchAllAssociative("
                SELECT id, nom, telephone 
                FROM livreur 
                WHERE est_archive = false 
                ORDER BY nom ASC
            ");

            foreach ($commandesEnAttente as &$commande) {
                $commande['total_formate'] = number_format($commande['total'], 0, ',', ' ') . ' FCFA';
                $commande['date_formate'] = date('d/m/Y H:i', strtotime($commande['date_commande']));
            }

            return $this->render('admin/livraison/list.html.twig', [
                'commandesEnAttente' => $commandesEnAttente,
                'livreurs' => $livreurs,
                'database_ready' => true
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur LivraisonController: " . $e->getMessage());
        }
    }

    #[Route('/assignation', name: 'app_livraison_assignation')]
    public function assignation(Request $request, Connection $connection): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $zoneFilter = $request->query->get('zone', 'all');
            $limit = self::LIMIT;
            $offset = ($page - 1) * $limit;

            $whereZone = $zoneFilter !== 'all' ? "AND c.zone_id = " . (int)$zoneFilter : '';

            $commandes = $connection->fetchAllAssociative("
                SELECT 
                    c.id,
                    'CMD' || c.id as numero_commande,
                    c.date_commande,
                    c.montant_total as total,
                    'Adresse livraison' as adresse_livraison,
                    'Téléphone client' as telephone_client,
                    'Client' as client_nom,
                    z.quartier,
                    z.prix as prix_livraison
                FROM commande c
                LEFT JOIN zone z ON c.zone_id = z.id
                WHERE c.statut IN ('VALIDEE', 'EN_COURS') 
                AND c.livreur_id IS NULL
                $whereZone
                ORDER BY c.date_commande ASC
                LIMIT $limit OFFSET $offset
            ");

            $totalCommandes = $connection->fetchOne("
                SELECT COUNT(DISTINCT c.id) 
                FROM commande c 
                WHERE c.statut IN ('VALIDEE', 'EN_COURS') 
                AND c.livreur_id IS NULL
                $whereZone
            ");
            $totalPages = (int) ceil($totalCommandes / $limit);

            $livreurs = $connection->fetchAllAssociative("
                SELECT id, nom, telephone 
                FROM livreur 
                WHERE est_archive = false 
                ORDER BY nom ASC
            ");

            $zones = $connection->fetchAllAssociative("
                SELECT id, quartier 
                FROM zone 
                WHERE est_archive = false 
                ORDER BY quartier ASC
            ");

            foreach ($commandes as &$commande) {
                $commande['total_formate'] = number_format($commande['total'], 0, ',', ' ') . ' FCFA';
                $commande['prix_livraison_formate'] = number_format($commande['prix_livraison'] ?? 0, 0, ',', ' ') . ' FCFA';
                $commande['date_formate'] = date('d/m/Y H:i', strtotime($commande['date_commande']));
                $commande['nb_produits'] = 1;
            }

            return $this->render('admin/livraison/assignation.html.twig', [
                'commandes' => $commandes,
                'livreurs' => $livreurs,
                'zones' => $zones,
                'pageEnCours' => $page,
                'nbrePage' => $totalPages,
                'totalCommandes' => $totalCommandes,
                'zoneFilter' => $zoneFilter,
                'database_ready' => true
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur assignation: " . $e->getMessage());
        }
    }

    #[Route('/assigner/{commandeId}', name: 'app_livraison_assigner', methods: ['POST'])]
    public function assigner(int $commandeId, Request $request, Connection $connection): JsonResponse
    {
        try {
            $livreurId = (int) $request->request->get('livreur_id');
            
            if (!$livreurId) {
                return $this->json(['error' => 'Livreur requis'], 400);
            }

            $commande = $connection->fetchAssociative("
                SELECT id, 'CMD' || id as numero_commande, statut 
                FROM commande 
                WHERE id = ? AND livreur_id IS NULL
            ", [$commandeId]);

            if (!$commande) {
                return $this->json(['error' => 'Commande non trouvée ou déjà assignée'], 404);
            }

            $livreur = $connection->fetchAssociative("
                SELECT id, nom 
                FROM livreur 
                WHERE id = ? AND est_archive = false
            ", [$livreurId]);

            if (!$livreur) {
                return $this->json(['error' => 'Livreur non trouvé'], 404);
            }

            $connection->executeStatement("
                UPDATE commande 
                SET livreur_id = ?, 
                    statut_livraison = 'ASSIGNEE',
                    statut = 'PRETE'
                WHERE id = ?
            ", [$livreurId, $commandeId]);

            return $this->json([
                'success' => true,
                'message' => "Commande #{$commande['numero_commande']} assignée à {$livreur['nom']}"
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}