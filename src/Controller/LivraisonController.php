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
            $stats = $this->getStatsLivraisons($connection);
            
            $commandesEnAttente = $connection->fetchAllAssociative("
                SELECT 
                    c.id,
                    c.id as numero_commande,
                    c.date_commande,
                    c.montant_total as total,
                    'Client ' || c.client_id as client_nom,
                    'Adresse client ' || c.client_id as adresse_livraison,
                    '77 123 45 67' as telephone_client,
                    c.zone_id,
                    COALESCE(z.quartier, 'Zone inconnue') as quartier,
                    COALESCE(z.prix, 600) as prix_livraison
                FROM commande c
                LEFT JOIN zone z ON c.zone_id = z.id
                WHERE c.statut IN ('VALIDEE', 'EN_COURS') 
                AND c.livreur_id IS NULL
                ORDER BY c.date_commande ASC
                LIMIT 5
            ");

            $livraisonsEnCours = $connection->fetchAllAssociative("
                SELECT 
                    c.id,
                    c.id as numero_commande,
                    c.date_commande,
                    c.montant_total as total,
                    'Adresse client ' || c.client_id as adresse_livraison,
                    'Client ' || c.client_id as client_nom,
                    c.zone_id,
                    l.nom as livreur_nom,
                    l.telephone as livreur_tel,
                    COALESCE(z.quartier, 'Zone inconnue') as quartier
                FROM commande c
                LEFT JOIN livreur l ON c.livreur_id = l.id
                LEFT JOIN zone z ON c.zone_id = z.id
                WHERE c.livreur_id IS NOT NULL 
                AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE')
                ORDER BY c.date_commande DESC
                LIMIT 10
            ");

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

            foreach ($commandesEnAttente as &$commande) {
                $commande['total_formate'] = number_format($commande['total'], 0, ',', ' ') . ' FCFA';
                $commande['prix_livraison_formate'] = number_format($commande['prix_livraison'], 0, ',', ' ') . ' FCFA';
                $commande['date_formate'] = date('d/m/Y H:i', strtotime($commande['date_commande']));
                $commande['priorite'] = $this->getPriorite($commande['date_commande']);
            }

            foreach ($livraisonsEnCours as &$livraison) {
                $livraison['total_formate'] = number_format($livraison['total'], 0, ',', ' ') . ' FCFA';
                $livraison['date_formate'] = date('d/m/Y H:i', strtotime($livraison['date_commande']));
                $livraison['statut_couleur'] = 'info';
            }

            return $this->render('admin/livraison/list.html.twig', [
                'stats' => $stats,
                'commandesEnAttente' => $commandesEnAttente,
                'livraisonsEnCours' => $livraisonsEnCours,
                'livreurs' => $livreurs,
                'zones' => $zones,
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
                    c.id as numero_commande,
                    c.date_commande,
                    c.montant_total as total,
                    'Adresse client ' || c.client_id as adresse_livraison,
                    '77 123 45 67' as telephone_client,
                    '' as note_commande,
                    'Client ' || c.client_id as client_nom,
                    c.zone_id,
                    COALESCE(z.quartier, 'Zone inconnue') as quartier,
                    COALESCE(z.prix, 600) as prix_livraison
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
                $commande['prix_livraison_formate'] = number_format($commande['prix_livraison'], 0, ',', ' ') . ' FCFA';
                $commande['total_avec_livraison'] = number_format($commande['total'] + $commande['prix_livraison'], 0, ',', ' ') . ' FCFA';
                $commande['date_formate'] = date('d/m/Y H:i', strtotime($commande['date_commande']));
                $commande['priorite'] = $this->getPriorite($commande['date_commande']);
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
                return $this->json(['success' => false, 'error' => 'Livreur requis'], 400);
            }

            $commande = $connection->fetchAssociative("
                SELECT id, statut 
                FROM commande 
                WHERE id = ? AND livreur_id IS NULL
            ", [$commandeId]);

            if (!$commande) {
                return $this->json(['success' => false, 'error' => 'Commande non trouvée ou déjà assignée'], 404);
            }

            $livreur = $connection->fetchAssociative("
                SELECT id, nom 
                FROM livreur 
                WHERE id = ? AND est_archive = false
            ", [$livreurId]);

            if (!$livreur) {
                return $this->json(['success' => false, 'error' => 'Livreur non trouvé'], 404);
            }

            $connection->executeStatement("
                UPDATE commande 
                SET livreur_id = ?, statut = 'PRETE'
                WHERE id = ?
            ", [$livreurId, $commandeId]);

            return $this->json([
                'success' => true,
                'message' => "Commande #CMD{$commandeId} assignée à {$livreur['nom']}"
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    #[Route('/details/{commandeId}', name: 'app_livraison_details', methods: ['GET'])]
    public function details(int $commandeId, Connection $connection): JsonResponse
    {
        try {
            $commande = $connection->fetchAssociative("
                SELECT 
                    c.id,
                    c.id as numero_commande,
                    c.date_commande,
                    c.montant_total as total,
                    'Adresse client ' || c.client_id as adresse_livraison,
                    '77 123 45 67' as telephone_client,
                    '' as note_commande,
                    c.statut,
                    c.zone_id,
                    'Client ' || c.client_id as client_nom,
                    'client@email.com' as client_email,
                    l.nom as livreur_nom,
                    l.telephone as livreur_tel,
                    COALESCE(z.quartier, 'Zone inconnue') as quartier,
                    COALESCE(z.prix, 600) as prix_livraison
                FROM commande c
                LEFT JOIN livreur l ON c.livreur_id = l.id
                LEFT JOIN zone z ON c.zone_id = z.id
                WHERE c.id = ?
            ", [$commandeId]);

            if (!$commande) {
                return $this->json(['success' => false, 'error' => 'Commande non trouvée'], 404);
            }

            $produits = $connection->fetchAllAssociative("
                SELECT 
                    CASE 
                        WHEN lc.produit_id IS NOT NULL THEN 'Produit #' || lc.produit_id
                        WHEN lc.menu_id IS NOT NULL THEN 'Menu #' || lc.menu_id
                        ELSE 'Article'
                    END as nom,
                    lc.quantite,
                    lc.prix_unitaire,
                    (lc.quantite * lc.prix_unitaire) as sous_total
                FROM ligne_commande lc
                WHERE lc.commande_id = ?
                ORDER BY lc.id ASC
            ", [$commandeId]);

            $commande['total_formate'] = number_format($commande['total'], 0, ',', ' ') . ' FCFA';
            $commande['prix_livraison_formate'] = number_format($commande['prix_livraison'], 0, ',', ' ') . ' FCFA';
            $commande['total_final'] = number_format($commande['total'] + $commande['prix_livraison'], 0, ',', ' ') . ' FCFA';
            $commande['date_formate'] = date('d/m/Y H:i', strtotime($commande['date_commande']));

            foreach ($produits as &$produit) {
                $produit['prix_unitaire_formate'] = number_format($produit['prix_unitaire'], 0, ',', ' ') . ' FCFA';
                $produit['sous_total_formate'] = number_format($produit['sous_total'], 0, ',', ' ') . ' FCFA';
            }

            return $this->json([
                'success' => true,
                'commande' => $commande,
                'produits' => $produits
            ]);

        } catch (\Exception $e) {
            return $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getStatsLivraisons(Connection $connection): array
    {
        try {
            $stats = [];

            $stats['en_attente'] = (int) $connection->fetchOne("
                SELECT COUNT(*) 
                FROM commande 
                WHERE statut IN ('VALIDEE', 'EN_COURS') 
                AND livreur_id IS NULL
            ") ?: 0;

            $stats['en_cours'] = (int) $connection->fetchOne("
                SELECT COUNT(*) 
                FROM commande 
                WHERE livreur_id IS NOT NULL 
                AND statut NOT IN ('TERMINEE')
            ") ?: 0;

            $stats['livrees_aujourd_hui'] = (int) $connection->fetchOne("
                SELECT COUNT(*) 
                FROM commande 
                WHERE statut = 'TERMINEE'
                AND DATE(date_commande) = CURRENT_DATE
            ") ?: 0;

            $caLivraisons = $connection->fetchOne("
                SELECT COALESCE(SUM(z.prix), 0)
                FROM commande c
                LEFT JOIN zone z ON c.zone_id = z.id
                WHERE c.statut = 'TERMINEE'
                AND DATE(c.date_commande) = CURRENT_DATE
            ");
            $stats['ca_livraisons_jour'] = number_format($caLivraisons ?: 0, 0, ',', ' ') . ' FCFA';

            return $stats;

        } catch (\Exception $e) {
            return [
                'en_attente' => 0,
                'en_cours' => 0,
                'livrees_aujourd_hui' => 0,
                'ca_livraisons_jour' => '0 FCFA'
            ];
        }
    }

    public function getPriorite(string $dateCommande): string
    {
        $heures = (time() - strtotime($dateCommande)) / 3600;
        
        if ($heures > 2) return 'urgent';
        if ($heures > 1) return 'normale';
        return 'recente';
    }
    #[Route('/suivi', name: 'app_livraison_suivi')]
public function suivi(Connection $connection): Response
{
    try {
        // Toutes les livraisons en cours
        $livraisonsEnCours = $connection->fetchAllAssociative("
            SELECT 
                c.id,
                c.id as numero_commande,
                c.date_commande,
                c.montant_total as total,
                'Client ' || c.client_id as client_nom,
                'Adresse client ' || c.client_id as adresse_livraison,
                '77 123 45 67' as telephone_client,
                c.zone_id,
                l.nom as livreur_nom,
                l.telephone as livreur_tel,
                COALESCE(z.quartier, 'Zone inconnue') as quartier,
                COALESCE(z.prix, 600) as prix_livraison,
                c.statut
            FROM commande c
            LEFT JOIN livreur l ON c.livreur_id = l.id
            LEFT JOIN zone z ON c.zone_id = z.id
            WHERE c.livreur_id IS NOT NULL 
            AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE')
            ORDER BY c.date_commande DESC
        ");

        $zones = $connection->fetchAllAssociative("
            SELECT DISTINCT z.id, z.quartier
            FROM zone z
            JOIN commande c ON z.id = c.zone_id
            WHERE c.livreur_id IS NOT NULL 
            AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE')
            ORDER BY z.quartier ASC
        ");

        foreach ($livraisonsEnCours as &$livraison) {
            $livraison['total_formate'] = number_format($livraison['total'], 0, ',', ' ') . ' FCFA';
            $livraison['prix_livraison_formate'] = number_format($livraison['prix_livraison'], 0, ',', ' ') . ' FCFA';
            $livraison['date_formate'] = date('d/m/Y H:i', strtotime($livraison['date_commande']));
            $livraison['statut_badge'] = $this->getStatutBadge($livraison['statut']);
            $livraison['temps_ecoule'] = $this->getTempsEcoule($livraison['date_commande']);
        }

        return $this->render('admin/livraison/suivi.html.twig', [
            'livraisonsEnCours' => $livraisonsEnCours,
            'zones' => $zones,
            'totalLivraisons' => count($livraisonsEnCours)
        ]);

    } catch (\Exception $e) {
        return new Response("Erreur suivi: " . $e->getMessage());
    }
}

private function getStatutBadge(string $statut): array
{
    $badges = [
        'VALIDEE' => ['class' => 'warning', 'text' => 'Préparation'],
        'EN_COURS' => ['class' => 'info', 'text' => 'En cours'],
        'PRETE' => ['class' => 'success', 'text' => 'En livraison']
    ];
    
    return $badges[$statut] ?? ['class' => 'secondary', 'text' => $statut];
}

private function getTempsEcoule(string $dateCommande): string
{
    $diff = time() - strtotime($dateCommande);
    $heures = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    if ($heures > 0) {
        return "{$heures}h{$minutes}min";
    }
    return "{$minutes}min";
}
}