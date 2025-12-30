<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/commande')]
class CommandeController extends AbstractController
{
    #[Route('/list', name: 'app_commande_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
            $statusFilter = $request->query->get('status', 'all');
            
            $commandes = $connection->fetchAllAssociative("
                SELECT c.id, c.client_id, c.montant_total, c.statut, 
                       c.date_commande, c.type_consommation
                FROM commande c 
                ORDER BY c.date_commande DESC
                LIMIT 10
            ");

            foreach ($commandes as &$commande) {
                $commande['numero'] = '#CMD-' . str_pad($commande['id'], 6, '0', STR_PAD_LEFT);
                $commande['client_nom'] = 'Client ' . $commande['client_id'];
                $commande['prix_formate'] = number_format($commande['montant_total'], 0, ',', ' ');
                $commande['heure'] = date('H:i', strtotime($commande['date_commande']));
            }

            return $this->render('admin/commande/list.html.twig', [
                'commandes' => $commandes,
                'statusFilter' => $statusFilter,
                'totalCommandes' => count($commandes)
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur CommandeController: " . $e->getMessage());
        }
    }

    #[Route('/details/{id}', name: 'app_commande_details', methods: ['GET'])]
    public function details(int $id, Connection $connection): JsonResponse
    {
        try {
            $commande = $connection->fetchAssociative("
                SELECT c.*, 
                       '#CMD-' || LPAD(c.id::text, 6, '0') as numero
                FROM commande c 
                WHERE c.id = ?
            ", [$id]);

            if (!$commande) {
                return $this->json(['error' => 'Commande non trouvée'], 404);
            }

            $produits = $connection->fetchAllAssociative("
                SELECT lc.*, p.nom as produit_nom, p.type_produit
                FROM ligne_commande lc
                JOIN produit p ON lc.produit_id = p.id
                WHERE lc.commande_id = ?
                ORDER BY p.nom
            ", [$id]);

            $commande['client_nom'] = 'Client ' . $commande['client_id'];
            $commande['prix_formate'] = number_format($commande['montant_total'], 0, ',', ' ');

            foreach ($produits as &$produit) {
                $produit['prix_formate'] = number_format($produit['prix_unitaire'], 0, ',', ' ');
                $produit['total_formate'] = number_format($produit['montant_total'], 0, ',', ' ');
            }

            return $this->json([
                'commande' => $commande,
                'produits' => $produits
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/statut/{id}', name: 'app_commande_change_statut', methods: ['POST'])]
    public function changeStatut(int $id, Request $request, Connection $connection): JsonResponse
    {
        try {
            $nouveauStatut = $request->request->get('statut');
            
            $statutsAutorises = ['EN_COURS', 'PRETE', 'EN_LIVRAISON', 'LIVREE', 'TERMINEE'];
            
            if (!in_array($nouveauStatut, $statutsAutorises)) {
                return $this->json(['error' => 'Statut non autorisé'], 400);
            }

            $commande = $connection->fetchAssociative("
                SELECT id FROM commande WHERE id = ?
            ", [$id]);

            if (!$commande) {
                return $this->json(['error' => 'Commande non trouvée'], 404);
            }

            $connection->executeStatement("
                UPDATE commande 
                SET statut = ? 
                WHERE id = ?
            ", [$nouveauStatut, $id]);

            return $this->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'nouveau_statut' => $nouveauStatut
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/annuler/{id}', name: 'app_commande_annuler', methods: ['POST'])]
    public function annuler(int $id, Connection $connection): JsonResponse
    {
        try {
            $commande = $connection->fetchAssociative("
                SELECT statut FROM commande WHERE id = ?
            ", [$id]);

            if (!$commande) {
                return $this->json(['error' => 'Commande non trouvée'], 404);
            }

            if (in_array($commande['statut'], ['LIVREE', 'TERMINEE', 'ANNULEE'])) {
                return $this->json(['error' => 'Cette commande ne peut plus être annulée'], 400);
            }

            $connection->executeStatement("
                UPDATE commande 
                SET statut = 'ANNULEE' 
                WHERE id = ?
            ", [$id]);

            return $this->json([
                'success' => true,
                'message' => 'Commande annulée avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}