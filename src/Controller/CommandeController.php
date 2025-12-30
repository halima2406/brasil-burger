<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
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
}