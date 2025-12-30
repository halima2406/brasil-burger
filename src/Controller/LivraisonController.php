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
                    'CMD' || c.id as numero_commande,
                    c.date_commande,
                    c.montant_total as total,
                    'Client' as client_nom,
                    'Adresse livraison' as adresse_livraison,
                    'Téléphone client' as telephone_client,
                    z.quartier,
                    z.prix as prix_livraison
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
                    'CMD' || c.id as numero_commande,
                    c.date_commande,
                    c.montant_total as total,
                    'Adresse livraison' as adresse_livraison,
                    'Client' as client_nom,
                    l.nom as livreur_nom,
                    l.telephone as livreur_tel,
                    z.quartier,
                    'ASSIGNEE' as statut_livraison
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
                $commande['prix_livraison_formate'] = number_format($commande['prix_livraison'] ?? 0, 0, ',', ' ') . ' FCFA';
                $commande['date_formate'] = date('d/m/Y H:i', strtotime($commande['date_commande']));
                $commande['priorite'] = $this->getPriorite($commande['date_commande']);
                $commande['nb_produits'] = 1;
            }

            foreach ($livraisonsEnCours as &$livraison) {
                $livraison['total_formate'] = number_format($livraison['total'], 0, ',', ' ') . ' FCFA';
                $livraison['date_formate'] = date('d/m/Y H:i', strtotime($livraison['date_commande']));
                $livraison['statut_couleur'] = $this->getStatutCouleur($livraison['statut_livraison']);
                $livraison['nb_produits'] = 1;
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
                    'CMD' || c.id as numero_commande,
                    c.date_commande,
                    c.montant_total as total,
                    'Adresse livraison' as adresse_livraison,
                    'Téléphone client' as telephone_client,
                    'Note commande' as note_commande,
                    'Client' as client_nom,
                    z.id as zone_id,
                    z.quartier,
                    z.prix as prix_livraison,
                    1 as nb_produits
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
                $commande['total_avec_livraison'] = number_format($commande['total'] + ($commande['prix_livraison'] ?? 0), 0, ',', ' ') . ' FCFA';
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

    #[Route('/suivi', name: 'app_livraison_suivi')]
    public function suivi(Request $request, Connection $connection): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $statutFilter = $request->query->get('statut', 'all');
            $livreurFilter = $request->query->get('livreur', 'all');
            $limit = self::LIMIT;
            $offset = ($page - 1) * $limit;

            $whereConditions = ["c.livreur_id IS NOT NULL"];
            
            if ($statutFilter !== 'all') {
                $whereConditions[] = "c.statut_livraison = '" . $connection->quote($statutFilter) . "'";
            }
            
            if ($livreurFilter !== 'all') {
                $whereConditions[] = "c.livreur_id = " . (int)$livreurFilter;
            }

            $whereClause = "WHERE " . implode(' AND ', $whereConditions);

            $livraisons = $connection->fetchAllAssociative("
                SELECT 
                    c.id,
                    'CMD' || c.id as numero_commande,
                    c.date_commande,
                    c.montant_total as total,
                    'Adresse livraison' as adresse_livraison,
                    'Téléphone client' as telephone_client,
                    c.statut,
                    COALESCE(c.statut_livraison, 'ASSIGNEE') as statut_livraison,
                    'Client' as client_nom,
                    l.nom as livreur_nom,
                    l.telephone as livreur_tel,
                    z.quartier,
                    z.prix as prix_livraison
                FROM commande c
                LEFT JOIN livreur l ON c.livreur_id = l.id
                LEFT JOIN zone z ON c.zone_id = z.id
                $whereClause
                ORDER BY c.date_commande DESC
                LIMIT $limit OFFSET $offset
            ");

            $totalLivraisons = $connection->fetchOne("
                SELECT COUNT(*) FROM commande c $whereClause
            ");
            $totalPages = (int) ceil($totalLivraisons / $limit);

            $livreurs = $connection->fetchAllAssociative("
                SELECT id, nom 
                FROM livreur 
                WHERE est_archive = false 
                ORDER BY nom ASC
            ");

            $statuts = [
                'ASSIGNEE' => 'Assignée',
                'EN_ROUTE' => 'En route',
                'LIVREE' => 'Livrée',
                'PROBLEME' => 'Problème'
            ];

            foreach ($livraisons as &$livraison) {
                $livraison['total_formate'] = number_format($livraison['total'], 0, ',', ' ') . ' FCFA';
                $livraison['prix_livraison_formate'] = number_format($livraison['prix_livraison'] ?? 0, 0, ',', ' ') . ' FCFA';
                $livraison['date_formate'] = date('d/m/Y H:i', strtotime($livraison['date_commande']));
                $livraison['statut_couleur'] = $this->getStatutCouleur($livraison['statut_livraison']);
                $livraison['duree'] = $this->getDureeLivraison($livraison['date_commande']);
            }

            return $this->render('admin/livraison/suivi.html.twig', [
                'livraisons' => $livraisons,
                'livreurs' => $livreurs,
                'statuts' => $statuts,
                'pageEnCours' => $page,
                'nbrePage' => $totalPages,
                'totalLivraisons' => $totalLivraisons,
                'statutFilter' => $statutFilter,
                'livreurFilter' => $livreurFilter,
                'database_ready' => true
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur suivi: " . $e->getMessage());
        }
    }

    #[Route('/changer-statut/{commandeId}', name: 'app_livraison_changer_statut', methods: ['POST'])]
    public function changerStatut(int $commandeId, Request $request, Connection $connection): JsonResponse
    {
        try {
            $nouveauStatut = $request->request->get('statut');
            
            $statutsValides = ['ASSIGNEE', 'EN_ROUTE', 'LIVREE', 'PROBLEME'];
            if (!in_array($nouveauStatut, $statutsValides)) {
                return $this->json(['error' => 'Statut invalide'], 400);
            }

            $result = $connection->executeStatement("
                UPDATE commande 
                SET statut_livraison = ?,
                    statut = CASE 
                        WHEN ? = 'LIVREE' THEN 'TERMINEE'
                        ELSE statut 
                    END
                WHERE id = ? AND livreur_id IS NOT NULL
            ", [$nouveauStatut, $nouveauStatut, $commandeId]);

            if ($result === 0) {
                return $this->json(['error' => 'Commande non trouvée'], 404);
            }

            return $this->json([
                'success' => true,
                'message' => 'Statut mis à jour avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/details/{commandeId}', name: 'app_livraison_details', methods: ['GET'])]
    public function details(int $commandeId, Connection $connection): JsonResponse
    {
        try {
            $commande = $connection->fetchAssociative("
                SELECT 
                    c.id,
                    'CMD' || c.id as numero_commande,
                    c.date_commande,
                    c.montant_total as total,
                    'Adresse livraison' as adresse_livraison,
                    'Téléphone client' as telephone_client,
                    'Note commande' as note_commande,
                    c.statut,
                    COALESCE(c.statut_livraison, 'EN_ATTENTE') as statut_livraison,
                    'Client' as client_nom,
                    'client@email.com' as client_email,
                    l.nom as livreur_nom,
                    l.telephone as livreur_tel,
                    z.quartier,
                    z.prix as prix_livraison
                FROM commande c
                LEFT JOIN livreur l ON c.livreur_id = l.id
                LEFT JOIN zone z ON c.zone_id = z.id
                WHERE c.id = ?
            ", [$commandeId]);

            if (!$commande) {
                return $this->json(['error' => 'Commande non trouvée'], 404);
            }

            $produits = $connection->fetchAllAssociative("
                SELECT 
                    p.nom,
                    lc.quantite,
                    lc.prix_unitaire,
                    (lc.quantite * lc.prix_unitaire) as sous_total
                FROM ligne_commande lc
                JOIN produit p ON lc.produit_id = p.id
                WHERE lc.commande_id = ?
                ORDER BY p.nom ASC
            ", [$commandeId]);

            $commande['total_formate'] = number_format($commande['total'], 0, ',', ' ') . ' FCFA';
            $commande['prix_livraison_formate'] = number_format($commande['prix_livraison'] ?? 0, 0, ',', ' ') . ' FCFA';
            $commande['total_final'] = number_format($commande['total'] + ($commande['prix_livraison'] ?? 0), 0, ',', ' ') . ' FCFA';
            $commande['date_formate'] = date('d/m/Y H:i', strtotime($commande['date_commande']));

            foreach ($produits as &$produit) {
                $produit['prix_unitaire_formate'] = number_format($produit['prix_unitaire'], 0, ',', ' ') . ' FCFA';
                $produit['sous_total_formate'] = number_format($produit['sous_total'], 0, ',', ' ') . ' FCFA';
            }

            return $this->json([
                'commande' => $commande,
                'produits' => $produits
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
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

            $stats['problemes'] = (int) $connection->fetchOne("
                SELECT COUNT(*) 
                FROM commande 
                WHERE statut_livraison = 'PROBLEME'
            ") ?: 0;

            $caLivraisons = $connection->fetchOne("
                SELECT COALESCE(SUM(z.prix), 0)
                FROM commande c
                LEFT JOIN zone z ON c.zone_id = z.id
                WHERE c.statut = 'TERMINEE'
                AND DATE(c.date_commande) = CURRENT_DATE
            ");
            $stats['ca_livraisons_jour'] = number_format($caLivraisons ?: 0, 0, ',', ' ') . ' FCFA';

            $livreurActif = $connection->fetchAssociative("
                SELECT l.nom, COUNT(*) as nb_livraisons
                FROM commande c
                LEFT JOIN livreur l ON c.livreur_id = l.id
                WHERE c.statut = 'TERMINEE'
                AND DATE(c.date_commande) = CURRENT_DATE
                AND l.nom IS NOT NULL
                GROUP BY l.id, l.nom
                ORDER BY nb_livraisons DESC
                LIMIT 1
            ");

            $stats['livreur_actif'] = $livreurActif ? 
                $livreurActif['nom'] . ' (' . $livreurActif['nb_livraisons'] . ' livraisons)' : 
                'Aucun';

            return $stats;

        } catch (\Exception $e) {
            return [
                'en_attente' => 0,
                'en_cours' => 0,
                'livrees_aujourd_hui' => 0,
                'problemes' => 0,
                'ca_livraisons_jour' => '0 FCFA',
                'livreur_actif' => 'Erreur'
            ];
        }
    }

    public function getStatutCouleur(string $statut): string
    {
        return match($statut) {
            'ASSIGNEE' => 'info',
            'EN_ROUTE' => 'warning',
            'LIVREE' => 'success',
            'PROBLEME' => 'danger',
            default => 'secondary'
        };
    }

    public function getPriorite(string $dateCommande): string
    {
        $heures = (time() - strtotime($dateCommande)) / 3600;
        
        if ($heures > 2) return 'urgent';
        if ($heures > 1) return 'normale';
        return 'recente';
    }

    public function getDureeLivraison(string $dateCommande): string
    {
        $minutes = (time() - strtotime($dateCommande)) / 60;
        
        if ($minutes < 60) {
            return floor($minutes) . ' min';
        } else {
            $heures = floor($minutes / 60);
            return $heures . 'h' . floor($minutes % 60) . 'm';
        }
    }
}