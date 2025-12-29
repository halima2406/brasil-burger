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

    #[Route('/edit/{id}', name: 'app_burger_edit', methods: ['POST'])]
    public function edit(int $id, Request $request, Connection $connection): Response
    {
        $nom = $request->request->get('nom');
        $prix = $request->request->get('prix');

        if (empty($nom) || !is_numeric($prix) || $prix < 0) {
            return $this->json(['error' => 'Données invalides'], 400);
        }

        $burger = $connection->fetchAssociative("
            SELECT id FROM produit WHERE id = ? AND type_produit = 'BURGER'
        ", [$id]);

        if (!$burger) {
            return $this->json(['error' => 'Burger non trouvé'], 404);
        }

        $connection->executeStatement("
            UPDATE produit SET nom = ?, prix = ? WHERE id = ?
        ", [$nom, $prix, $id]);

        return $this->json([
            'success' => true,
            'message' => 'Burger modifié avec succès'
        ]);
    }
}