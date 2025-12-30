<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/complement')]
class ComplementController extends AbstractController
{
    #[Route('/list', name: 'app_complement_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
            $filter = $request->query->get('filter', 'tous');
            
            $whereCondition = $this->getWhereConditionForFilter($filter);
            
            $complements = $connection->fetchAllAssociative("
                SELECT 
                    p.id,
                    p.nom,
                    p.prix,
                    p.type_produit,
                    p.type_complement
                FROM produit p
                WHERE $whereCondition
                ORDER BY p.nom ASC
                LIMIT 10
            ");

            foreach ($complements as &$complement) {
                $complement['prix_formate'] = number_format($complement['prix'], 0, ',', ' ') . ' FCFA';
                $complement['disponible'] = $complement['prix'] > 0;
                $complement['description'] = 'Complément savoureux Brasil Burger';
            }

            return $this->render('admin/complement/list.html.twig', [
                'complements' => $complements,
                'filter' => $filter,
                'totalComplements' => count($complements)
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur ComplementController: " . $e->getMessage());
        }
    }

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
}