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
}