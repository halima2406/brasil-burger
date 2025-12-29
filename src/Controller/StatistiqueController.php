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
            $data = [
                'topVentes' => [],
                'recettesJouralieres' => ['burgers' => 0, 'menus' => 0, 'frites' => 0, 'boissons' => 0, 'total' => 0],
                'commandesValidees' => [],
                'commandesAnnulees' => [],
                'totalVentes' => 0,
                'totalRecettes' => 0
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
}