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
    /*#[Route('/list', name: 'app_commande_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
            $statusFilter = $request->query->get('status', 'all');
            $search = $request->query->get('search', '');
            
            $toutesLesCommandes = $connection->fetchAllAssociative("
                SELECT c.id, c.client_id, c.montant_total, c.statut, 
                       c.date_commande, c.type_consommation
                FROM commande c 
                ORDER BY c.date_commande DESC
            ");

            foreach ($toutesLesCommandes as &$commande) {
                $commande['numero'] = '#CMD-' . str_pad($commande['id'], 6, '0', STR_PAD_LEFT);
                $commande['client_nom'] = 'Client ' . $commande['client_id'];
                $commande['telephone'] = '77 ' . str_pad($commande['client_id'] * 123, 7, '0', STR_PAD_LEFT);
                $commande['prix_formate'] = number_format($commande['montant_total'], 0, ',', ' ');
                $commande['heure'] = date('H:i', strtotime($commande['date_commande']));
                $commande['client_initiales'] = $this->getClientInitiales($commande['client_nom']);
                $commande['mode_badge'] = $this->getModeBadge($commande['type_consommation']);
                $commande['status_badge'] = $this->getStatusBadge($commande['statut']);
                $commande['articles_count'] = rand(1, 5) . ' articles';
            }

            $stats = $this->calculerStatistiquesReelles($toutesLesCommandes);
            $commandesFiltrees = $this->filtrerCommandes($toutesLesCommandes, $statusFilter, $search);
            $commandes = array_slice($commandesFiltrees, 0, 10);

            return $this->render('admin/commande/list.html.twig', [
                'commandes' => $commandes,
                'stats' => $stats,
                'statusFilter' => $statusFilter,
                'search' => $search,
                'totalCommandes' => count($commandesFiltrees)
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur CommandeController: " . $e->getMessage());
        }
    }*/

    #[Route('/list', name: 'app_commande_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
            $statusFilter = $request->query->get('status', 'all');
            $search = trim($request->query->get('search', ''));
            $page = max(1, (int) $request->query->get('page', 1));
            $perPage = 4;
            $offset = ($page - 1) * $perPage;
            
            $toutesLesCommandes = $connection->fetchAllAssociative("
                SELECT c.id, c.client_id, c.montant_total, c.statut, 
                    c.date_commande, c.type_consommation
                FROM commande c 
                ORDER BY c.date_commande DESC
            ");

            foreach ($toutesLesCommandes as &$commande) {
                $commande['numero'] = '#CMD-' . str_pad($commande['id'], 6, '0', STR_PAD_LEFT);
                $commande['client_nom'] = 'Client ' . $commande['client_id'];
                $commande['telephone'] = '77 ' . str_pad($commande['client_id'] * 123, 7, '0', STR_PAD_LEFT);
                $commande['prix_formate'] = number_format($commande['montant_total'], 0, ',', ' ');
                $commande['heure'] = date('H:i', strtotime($commande['date_commande']));
                $commande['client_initiales'] = $this->getClientInitiales($commande['client_nom']);
                $commande['mode_badge'] = $this->getModeBadge($commande['type_consommation']);
                $commande['status_badge'] = $this->getStatusBadge($commande['statut']);
                $commande['articles_count'] = rand(1, 5) . ' articles';
            }

            $stats = $this->calculerStatistiquesReelles($toutesLesCommandes);
            $commandesFiltrees = $this->filtrerCommandes($toutesLesCommandes, $statusFilter, $search);
            
            
            $totalItems = count($commandesFiltrees);
            $totalPages = ceil($totalItems / $perPage);
            $commandes = array_slice($commandesFiltrees, $offset, $perPage);

            return $this->render('admin/commande/list.html.twig', [
                'commandes' => $commandes,
                'stats' => $stats,
                'statusFilter' => $statusFilter,
                'search' => $search,
                'totalCommandes' => $totalItems,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $totalItems,
                    'per_page' => $perPage,
                    'has_previous' => $page > 1,
                    'has_next' => $page < $totalPages,
                    'previous_page' => $page > 1 ? $page - 1 : null,
                    'next_page' => $page < $totalPages ? $page + 1 : null,
                    'pages' => range(1, $totalPages)
                ]
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
            $commande['status_badge'] = $this->getStatusBadge($commande['statut']);
            $commande['mode_badge'] = $this->getModeBadge($commande['type_consommation']);

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

    public function filtrerCommandes(array $commandes, string $statusFilter, string $search): array
    {
        $filtered = $commandes;

        if ($statusFilter !== 'all') {
            $filtered = array_filter($filtered, function($cmd) use ($statusFilter) {
                return match($statusFilter) {
                    'pending' => $cmd['statut'] === 'VALIDEE',
                    'preparing' => $cmd['statut'] === 'EN_COURS',  
                    'delivering' => in_array($cmd['statut'], ['PRETE', 'EN_LIVRAISON']),
                    'completed' => in_array($cmd['statut'], ['LIVREE', 'TERMINEE']),
                    'cancelled' => $cmd['statut'] === 'ANNULEE',
                    default => true
                };
            });
        }

        if (!empty($search)) {
            $filtered = array_filter($filtered, function($cmd) use ($search) {
                return stripos($cmd['client_nom'] ?? '', $search) !== false || 
                    stripos($cmd['numero'], $search) !== false;
            });
        }

        return array_values($filtered);
    }

    public function calculerStatistiquesReelles(array $commandes): array
    {
        $stats = [
            'all' => count($commandes),
            'pending' => 0,
            'preparing' => 0,
            'delivering' => 0,
            'completed' => 0,
            'cancelled' => 0
        ];

        foreach ($commandes as $commande) {
            switch ($commande['statut']) {
                case 'VALIDEE':
                    $stats['pending']++;
                    break;
                case 'EN_COURS':
                    $stats['preparing']++;
                    break;
                case 'PRETE':
                case 'EN_LIVRAISON':
                    $stats['delivering']++;
                    break;
                case 'LIVREE':
                case 'TERMINEE':
                    $stats['completed']++;
                    break;
                case 'ANNULEE':
                    $stats['cancelled']++;
                    break;
            }
        }

        return $stats;
    }

    public function getClientInitiales(?string $nom): string
    {
        if (!$nom) return 'CL';
        $parts = explode(' ', trim($nom));
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }
        return strtoupper(substr($nom, 0, 2));
    }

    public function getModeBadge(?string $type): array
    {
        return match($type) {
            'A_EMPORTER' => ['icon' => '📦', 'text' => 'Emporter', 'class' => 'emporter'],
            'LIVRAISON' => ['icon' => '🏍️', 'text' => 'Livraison', 'class' => 'livraison'], 
            'SUR_PLACE' => ['icon' => '🍽️', 'text' => 'Sur place', 'class' => 'surplace'],
            default => ['icon' => '🏍️', 'text' => 'Livraison', 'class' => 'livraison']
        };
    }

    public function getStatusBadge(?string $statut): array
    {
        return match($statut) {
            'VALIDEE' => ['text' => 'En attente', 'class' => 'pending'],
            'EN_COURS' => ['text' => 'Préparation', 'class' => 'preparing'],
            'PRETE', 'EN_LIVRAISON' => ['text' => 'Livraison', 'class' => 'delivering'],
            'LIVREE', 'TERMINEE' => ['text' => 'Terminée', 'class' => 'completed'],
            'ANNULEE' => ['text' => 'Annulée', 'class' => 'cancelled'],
            default => ['text' => 'En attente', 'class' => 'pending']
        };
    }
}