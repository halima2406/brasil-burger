<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

#[Route('/admin/menu')]
class MenuController extends AbstractController
{
    #[Route('/list', name: 'app_menu_list')]
    public function list(Request $request, Connection $connection): Response
    {
        try {
            $menus = $connection->fetchAllAssociative("
                SELECT 
                    m.id,
                    m.nom,
                    m.est_archive,
                    b.nom as burger_nom,
                    b.prix as burger_prix,
                    bo.nom as boisson_nom,
                    bo.prix as boisson_prix,
                    f.nom as frite_nom,
                    f.prix as frite_prix,
                    COALESCE(b.prix, 0) + COALESCE(bo.prix, 0) + COALESCE(f.prix, 0) as prix_total
                FROM menu m
                LEFT JOIN produit b ON m.burger_id = b.id
                LEFT JOIN produit bo ON m.boisson_id = bo.id  
                LEFT JOIN produit f ON m.frite_id = f.id
                WHERE m.est_archive = false OR m.est_archive IS NULL
                ORDER BY m.nom ASC
                LIMIT 10
            ");

            foreach ($menus as &$menu) {
                $menu['prix'] = (float) $menu['prix_total'];
                $menu['prix_formate'] = number_format($menu['prix'], 0, ',', ' ') . ' FCFA';
                $menu['disponible'] = !$menu['est_archive'];
                $menu['description'] = 'Menu complet Brasil Burger';
            }

            return $this->render('admin/menu/list.html.twig', [
                'menus' => $menus,
                'totalMenus' => count($menus)
            ]);

        } catch (\Exception $e) {
            return new Response("Erreur MenuController: " . $e->getMessage());
        }
    }
}