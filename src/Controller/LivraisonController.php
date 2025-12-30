<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/livraison')]
class LivraisonController extends AbstractController
{
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
}