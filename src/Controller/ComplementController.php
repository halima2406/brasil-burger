<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/complement')]
final class ComplementController extends AbstractController
{
    private const LIMIT = 4; // ✅ 6 LIGNES MAX PAR PAGE

    #[Route('/list', name: 'app_complement_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $filter = $request->query->get('filter', 'tous'); // ✅ FILTRE PAR CATÉGORIE
            $limit = self::LIMIT;
            $offset = ($page - 1) * $limit;

            // ✅ CONSTRUIRE LA CONDITION WHERE SELON LE FILTRE
            $whereCondition = $this->getWhereConditionForFilter($filter);
            
            // ✅ RÉCUPÉRER LES COMPLÉMENTS SELON LE FILTRE
            $complements = $connection->fetchAllAssociative("
                SELECT 
                    p.id,
                    p.nom,
                    p.prix,
                    p.type_produit,
                    p.type_complement,
                    -- Statistiques de ventes
                    COUNT(lc.id) as ventes_totales,
                    SUM(CASE WHEN DATE(c.date_commande) = CURRENT_DATE THEN lc.quantite ELSE 0 END) as ventes_jour,
                    SUM(lc.prix_unitaire * lc.quantite) as chiffre_affaires_total
                FROM produit p
                LEFT JOIN ligne_commande lc ON p.id = lc.produit_id
                LEFT JOIN commande c ON lc.commande_id = c.id AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                WHERE $whereCondition
                GROUP BY p.id, p.nom, p.prix, p.type_produit, p.type_complement
                ORDER BY 
                    CASE 
                        WHEN p.type_complement = 'FRITE' THEN 1
                        WHEN p.type_complement = 'BOISSON' THEN 2
                        WHEN p.type_produit = 'COMPLEMENT' THEN 3
                        ELSE 4
                    END, p.nom ASC
                LIMIT $limit OFFSET $offset
            ");

            // ✅ ENRICHIR LES DONNÉES POUR LE TEMPLATE
            foreach ($complements as &$complement) {
                $complement['prix_formate'] = number_format($complement['prix'], 0, ',', ' ') . ' FCFA';
                $complement['ventes_jour'] = (int) $complement['ventes_jour'] ?: 0;
                $complement['ventes_totales'] = (int) $complement['ventes_totales'] ?: 0;
                
                // ✅ AFFICHAGE VENTES FORMATÉ
                $complement['ventes_affichage'] = $complement['ventes_totales'] . ' ventes';
                if ($complement['ventes_jour'] > 0) {
                    $complement['ventes_jour_affichage'] = $complement['ventes_jour'] . ' vendus';
                }
                
                $complement['chiffre_affaires_formate'] = number_format($complement['chiffre_affaires_total'], 0, ',', ' ') . ' FCFA';
                
                // Statut basé sur le prix
                $complement['disponible'] = $complement['prix'] > 0;
                $complement['archive'] = false;
                
                // Popularité
                $complement['popularite'] = $this->getPopularite($complement['ventes_totales']);
                
                // ✅ DÉTERMINER LE TYPE D'AFFICHAGE
                $typeAffichage = $complement['type_complement'] ?: $complement['type_produit'];
                
                // ✅ CATÉGORIE POUR LE STYLE ET L'AFFICHAGE
                switch ($typeAffichage) {
                    case 'FRITE':
                        $complement['categorie'] = 'frite';
                        $complement['type_display'] = 'FRITE';
                        break;
                    case 'BOISSON':
                        $complement['categorie'] = 'boisson';
                        $complement['type_display'] = 'BOISSON';
                        break;
                    case 'COMPLEMENT':
                        $complement['categorie'] = 'accompagnement';
                        $complement['type_display'] = 'COMPLEMENT';
                        break;
                    default:
                        $complement['categorie'] = 'accompagnement';
                        $complement['type_display'] = $typeAffichage;
                }
                
                // Description automatique
                $complement['description'] = $this->getDescription($complement['nom'], $typeAffichage);
                
                // ✅ IMAGE URL POUR TEMPLATE
                $complement['image_url'] = $this->getImageUrl($complement['nom'], $typeAffichage);
            }

            // ✅ PAGINATION SPÉCIFIQUE AU FILTRE ACTUEL
            // ✅ CORRECTION : ajouter l'alias "p"
            $totalComplements = $connection->fetchOne("
            SELECT COUNT(*) FROM produit p WHERE $whereCondition
            ");
            $totalPages = (int) ceil($totalComplements / $limit);

            // ✅ STATISTIQUES RÉELLES
            $stats = $this->getStatsComplements($connection);

            return $this->render('admin/complement/list.html.twig', [
                'complements' => $complements,
                'pageEnCours' => $page,
                'nbrePage' => $totalPages,
                'totalComplements' => $totalComplements,
                'filter' => $filter, // ✅ PASSER LE FILTRE ACTUEL AU TEMPLATE
                'stats' => $stats,
                'database_ready' => true
            ]);

        } catch (\Exception $e) {
            return new Response("
                <h1>ERREUR CONTROLLER COMPLÉMENT</h1>
                <p>Message: " . htmlspecialchars($e->getMessage()) . "</p>
                <p>Trace: <pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></p>
                <hr>
                <a href='/admin'>→ Retour au Dashboard</a>
            ");
        }
    }

    // ✅ NOUVELLE MÉTHODE POUR CONSTRUIRE LA CONDITION WHERE SELON LE FILTRE
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

    // ✅ ROUTE DÉTAILS
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

            // Commandes récentes
            $commandesRecentes = $connection->fetchAllAssociative("
                SELECT c.id, c.date_commande, lc.quantite, lc.prix_unitaire, c.statut
                FROM ligne_commande lc
                JOIN commande c ON lc.commande_id = c.id
                WHERE lc.produit_id = ? AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                ORDER BY c.date_commande DESC
                LIMIT 10
            ", [$id]);

            // Formatage
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

    // ✅ ROUTE MODIFIER
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

    // ✅ ROUTE SUPPRIMER
    #[Route('/delete/{id}', name: 'app_complement_delete', methods: ['POST'])]
    public function delete(int $id, Connection $connection): JsonResponse
    {
        try {
            // Vérifier que le complément existe
            $complement = $connection->fetchAssociative("
                SELECT id, nom FROM produit WHERE id = ?
            ", [$id]);

            if (!$complement) {
                return $this->json(['error' => 'Complément non trouvé'], 404);
            }

            // Vérifier s'il y a des commandes liées
            $commandesLiees = $connection->fetchOne("
                SELECT COUNT(*) FROM ligne_commande WHERE produit_id = ?
            ", [$id]);

            if ($commandesLiees > 0) {
                return $this->json(['error' => 'Impossible de supprimer : complément lié à des commandes'], 400);
            }

            // Supprimer définitivement
            $connection->executeStatement("DELETE FROM produit WHERE id = ?", [$id]);

            return $this->json([
                'success' => true,
                'message' => 'Complément supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getStatsComplements(Connection $connection): array
    {
        try {
            // ✅ STATISTIQUES SANS SAUCES NI DESSERTS
            $stats = [];
            
            // TOTAL (TOUS)
            $stats['total_complements'] = (int) $connection->fetchOne("
                SELECT COUNT(*) FROM produit p
                WHERE p.type_complement IN ('FRITE', 'BOISSON') OR p.type_produit = 'COMPLEMENT'
            ") ?: 0;

            // FRITES (ACCOMPAGNEMENTS)
            $stats['accompagnements'] = (int) $connection->fetchOne("
                SELECT COUNT(*) FROM produit p WHERE p.type_complement = 'FRITE'
            ") ?: 0;

            $stats['boissons'] = (int) $connection->fetchOne("
                SELECT COUNT(*) FROM produit p WHERE p.type_complement = 'BOISSON'
            ") ?: 0;

            // COMPLÉMENTS (SALADE, NUGGETS)
            $stats['complements'] = (int) $connection->fetchOne("
                SELECT COUNT(*) FROM produit p WHERE p.type_produit = 'COMPLEMENT'
            ") ?: 0;

            // Complément le plus vendu
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

            $stats['complement_populaire'] = $complementPopulaire ? $complementPopulaire['nom'] : 'Aucun';

            // Ventes du jour
            $ventesJour = $connection->fetchAssociative("
                SELECT 
                    COALESCE(SUM(lc.quantite), 0) as quantite,
                    COALESCE(SUM(lc.prix_unitaire * lc.quantite), 0) as ca
                FROM ligne_commande lc
                JOIN commande c ON lc.commande_id = c.id
                JOIN produit p ON lc.produit_id = p.id
                WHERE DATE(c.date_commande) = CURRENT_DATE 
                AND c.statut IN ('VALIDEE', 'EN_COURS', 'PRETE', 'LIVREE', 'TERMINEE')
                AND (p.type_complement IN ('FRITE', 'BOISSON') OR p.type_produit = 'COMPLEMENT')
            ");

            $stats['sold_today'] = (int) $ventesJour['quantite'];
            $stats['ca_today'] = number_format($ventesJour['ca'], 0, ',', ' ') . ' FCFA';

            return $stats;

        } catch (\Exception $e) {
            return [
                'total_complements' => 0,
                'accompagnements' => 0,
                'boissons' => 0,
                'complements' => 0,
                'complement_populaire' => 'Erreur',
                'sold_today' => 0,
                'ca_today' => '0 FCFA'
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

    private function getImageUrl(string $nom, string $type): string
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