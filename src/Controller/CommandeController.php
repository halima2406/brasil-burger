<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/commande')]
final class CommandeController extends AbstractController
{
    private const LIMIT = 5;

    #[Route('/list', name: 'app_commande_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $search = $request->query->get('search', '');
            $statusFilter = $request->query->get('status', 'all');
            $limit = self::LIMIT;
            $offset = ($page - 1) * $limit;

            // ✅ CORRIGÉ : Sans table client 
            $toutesLesCommandes = $connection->fetchAllAssociative("
                SELECT c.id, c.client_id, c.montant_total, c.statut, 
                       c.date_commande, c.type_consommation
                FROM commande c 
                ORDER BY c.date_commande DESC
            ");

            // ✅ GÉNÉRER TOUTES LES DONNÉES MANQUANTES
            foreach ($toutesLesCommandes as &$commande) {
                $commande['numero'] = '#CMD-' . str_pad($commande['id'], 6, '0', STR_PAD_LEFT);
                $commande['client_nom'] = 'Client ' . $commande['client_id']; // Nom fictif basé sur ID
                $commande['telephone'] = '77 ' . str_pad($commande['client_id'] * 123, 7, '0', STR_PAD_LEFT); // Téléphone fictif
            }

            // Calculer les statistiques VRAIES
            $stats = $this->calculerStatistiquesReelles($toutesLesCommandes);

            // Filtrer par statut ET par recherche
            $commandesFiltrees = $this->filtrerCommandes($toutesLesCommandes, $statusFilter, $search);

            // Paginer seulement les commandes filtrées
            $commandes = array_slice($commandesFiltrees, $offset, $limit);

            // Enrichir pour l'affichage
            foreach ($commandes as &$commande) {
                $commande['client_initiales'] = $this->getClientInitiales($commande['client_nom']);
                $commande['mode_badge'] = $this->getModeBadge($commande['type_consommation']);
                $commande['status_badge'] = $this->getStatusBadge($commande['statut']);
                $commande['heure'] = date('H:i', strtotime($commande['date_commande']));
                $commande['prix_formate'] = number_format($commande['montant_total'], 0, ',', ' ');
                $commande['articles_count'] = rand(1, 5) . ' articles'; // À remplacer par vraie logique
            }

            $totalPages = (int) ceil(count($commandesFiltrees) / $limit);

            return $this->render('admin/commande/list.html.twig', [
                'commandes' => $commandes,
                'stats' => $stats,
                'statusFilter' => $statusFilter,
                'pageEnCours' => $page,
                'nbrePage' => $totalPages,
                'totalCommandes' => count($commandesFiltrees),
                'search' => $search
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur base de données: " . $e->getMessage());
        }
    }

    // ✅ NOUVELLE ROUTE : Voir détails d'une commande
    #[Route('/details/{id}', name: 'app_commande_details', methods: ['GET'])]
    public function details(int $id, Connection $connection): Response
    {
        try {
            // Récupérer la commande
            $commande = $connection->fetchAssociative("
                SELECT c.*, 
                       '#CMD-' || LPAD(c.id::text, 6, '0') as numero
                FROM commande c 
                WHERE c.id = ?
            ", [$id]);

            if (!$commande) {
                return $this->json(['error' => 'Commande non trouvée'], 404);
            }

            // Récupérer les produits de la commande
            $produits = $connection->fetchAllAssociative("
                SELECT lc.*, p.nom as produit_nom, p.type_produit
                FROM ligne_commande lc
                JOIN produit p ON lc.produit_id = p.id
                WHERE lc.commande_id = ?
                ORDER BY p.nom
            ", [$id]);

            // Enrichir les données
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

    // ✅ NOUVELLE ROUTE : Changer le statut d'une commande
    #[Route('/statut/{id}', name: 'app_commande_change_statut', methods: ['POST'])]
    public function changeStatut(int $id, Request $request, Connection $connection): Response
    {
        try {
            $nouveauStatut = $request->request->get('statut');
            
            // Validation des statuts autorisés
            $statutsAutorises = ['EN_COURS', 'PRETE', 'EN_LIVRAISON', 'LIVREE', 'TERMINEE'];
            
            if (!in_array($nouveauStatut, $statutsAutorises)) {
                return $this->json(['error' => 'Statut non autorisé'], 400);
            }

            // Mettre à jour le statut
            $result = $connection->executeStatement("
                UPDATE commande 
                SET statut = ? 
                WHERE id = ?
            ", [$nouveauStatut, $id]);

            if ($result === 0) {
                return $this->json(['error' => 'Commande non trouvée'], 404);
            }

            return $this->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès',
                'nouveau_statut' => $nouveauStatut
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // ✅ NOUVELLE ROUTE : Annuler une commande
    #[Route('/annuler/{id}', name: 'app_commande_annuler', methods: ['POST'])]
    public function annuler(int $id, Connection $connection): Response
    {
        try {
            // Vérifier que la commande existe et peut être annulée
            $commande = $connection->fetchAssociative("
                SELECT statut FROM commande WHERE id = ?
            ", [$id]);

            if (!$commande) {
                return $this->json(['error' => 'Commande non trouvée'], 404);
            }

            // Vérifier que la commande n'est pas déjà terminée
            if (in_array($commande['statut'], ['LIVREE', 'TERMINEE', 'ANNULEE'])) {
                return $this->json(['error' => 'Cette commande ne peut plus être annulée'], 400);
            }

            // Annuler la commande
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

    private function filtrerCommandes(array $commandes, string $statusFilter, string $search): array
    {
        $filtered = $commandes;

        // Filtrer par statut
        if ($statusFilter !== 'all') {
            $filtered = array_filter($filtered, function($cmd) use ($statusFilter) {
                return match($statusFilter) {
                    // ✅ CORRIGÉ : Utiliser les VRAIS statuts de la base
                    'pending' => $cmd['statut'] === 'VALIDEE',
                    'preparing' => $cmd['statut'] === 'EN_COURS',  
                    'delivering' => in_array($cmd['statut'], ['PRETE', 'EN_LIVRAISON']),
                    'completed' => in_array($cmd['statut'], ['LIVREE', 'TERMINEE']),
                    'cancelled' => $cmd['statut'] === 'ANNULEE',
                    default => true
                };
            });
        }

        // Filtrer par recherche
        if (!empty($search)) {
            $filtered = array_filter($filtered, function($cmd) use ($search) {
                return stripos($cmd['client_nom'] ?? '', $search) !== false || 
                    stripos($cmd['numero'], $search) !== false || 
                    stripos($cmd['telephone'] ?? '', $search) !== false;
            });
        }

        return array_values($filtered);
    }

    private function calculerStatistiquesReelles(array $commandes): array
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
            // ✅ NOUVEAU CODE RÉEL
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

    private function getClientInitiales(?string $nom): string
    {
        if (!$nom) return 'CL';
        $parts = explode(' ', trim($nom));
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }
        return strtoupper(substr($nom, 0, 2));
    }

    private function getModeBadge(?string $type): array
    {
        return match($type) {
            'A_EMPORTER' => ['icon' => '📦', 'text' => 'Emporter', 'class' => 'emporter'],
            'LIVRAISON' => ['icon' => '🏍️', 'text' => 'Livraison', 'class' => 'livraison'], 
            'SUR_PLACE' => ['icon' => '🍽️', 'text' => 'Sur place', 'class' => 'surplace'],
            default => ['icon' => '🏍️', 'text' => 'Livraison', 'class' => 'livraison']
        };
    }

    private function getStatusBadge(?string $statut): array
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