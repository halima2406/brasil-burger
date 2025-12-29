<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/burger')]
class BurgerController extends AbstractController
{
    #[Route('/list', name: 'app_burger_list')]
    public function list(Request $request, Connection $connection): Response
    {
        $burgers = $connection->fetchAllAssociative("
            SELECT p.id, p.nom, p.prix, p.type_produit
            FROM produit p 
            WHERE p.type_produit = 'BURGER'
            ORDER BY p.nom ASC
        ");

        foreach ($burgers as &$burger) {
            $burger['prix_formate'] = number_format($burger['prix'], 0, ',', ' ');
            $burger['disponible'] = $burger['prix'] > 0;
            $burger['statut'] = $burger['disponible'] ? 'Actif' : 'Inactif';
        }

        return $this->render('admin/burger/list.html.twig', [
            'burgers' => $burgers
        ]);
    }

    #[Route('/details/{id}', name: 'app_burger_details')]
    public function details(int $id, Connection $connection): Response
    {
        $burger = $connection->fetchAssociative("
            SELECT p.id, p.nom, p.prix, p.type_produit
            FROM produit p 
            WHERE p.id = ? AND p.type_produit = 'BURGER'
        ", [$id]);

        if (!$burger) {
            return $this->json(['error' => 'Burger non trouvé'], 404);
        }

        $burger['prix_formate'] = number_format($burger['prix'], 0, ',', ' ');
        $burger['disponible'] = $burger['prix'] > 0;

        return $this->json([
            'success' => true,
            'burger' => $burger
        ]);
    }
}