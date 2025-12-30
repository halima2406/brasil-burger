<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

class StatistiqueController extends AbstractController
{
    #[Route('/admin/statistiques', name: 'app_statistiques')]
    public function index(Connection $connection): Response
    {
        try {
            $statsJour = $this->getStatistiquesJournalieres($connection);
            
            $data = [
                'topVentes' => [],
                'recettesJouralieres' => ['burgers' => 0, 'menus' => 0, 'frites' => 0, 'boissons' => 0, 'total' => 0],
                'commandesValidees' => [],
                'commandesAnnulees' => [],
                'totalVentes' => $statsJour['commandes_validees'],
                'totalRecettes' => $statsJour['recettes_jour'],
                'stats_jour' => $statsJour
            ];
            
            return $this->render('admin/statistiques/index.html.twig', $data);
            
        } catch (\Exception $e) {
            return $this->render('admin/statistiques/index.html.twig', [
                'error' => $e->getMessage(),
                'topVentes' => [],
                'recettesJouralieres' => ['burgers' => 0, 'menus' => 0, 'frites' => 0, 'boissons' => 0, 'total' => 0]
            ]);
        }
    }

    private function getStatistiquesJournalieres(Connection $connection): array
    {
        $aujourdhui = date('Y-m-d');
        
        $commandesValidees = $connection->fetchOne("
            SELECT COUNT(*) FROM commande 
            WHERE DATE(date_commande) = ? AND statut = 'VALIDEE'
        ", [$aujourdhui]) ?: 0;
        
        $commandesAnnulees = $connection->fetchOne("
            SELECT COUNT(*) FROM commande 
            WHERE DATE(date_commande) = ? AND statut = 'ANNULEE'
        ", [$aujourdhui]) ?: 0;
        
        $recettesJour = $connection->fetchOne("
            SELECT COALESCE(SUM(montant_total), 0) FROM commande 
            WHERE DATE(date_commande) = ? AND statut IN ('VALIDEE', 'EN_COURS', 'TERMINEE')
        ", [$aujourdhui]) ?: 0;
        
        return [
            'commandes_validees' => (int) $commandesValidees,
            'commandes_annulees' => (int) $commandesAnnulees,
            'recettes_jour' => (float) $recettesJour
        ];
    }
}