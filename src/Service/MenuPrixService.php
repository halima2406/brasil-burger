<?php

namespace App\Service;

use App\Entity\Menu;
use App\Entity\Produit;

class MenuPrixService
{
    public function calculerPrixMenu(Menu $menu): float
    {
        return $menu->getPrix();
    }

    public function calculerPrixPersonnalise(Produit $burger, Produit $boisson, Produit $frite): float
    {
        return $burger->getPrix() + $boisson->getPrix() + $frite->getPrix();
    }

    public function formaterPrix(float $prix): string
    {
        return number_format($prix, 0, ',', ' ') . ' FCFA';
    }

    public function verifierMenu(Menu $menu): array
    {
        return [
            'menu_id' => $menu->getId(),
            'menu_nom' => $menu->getNom(),
            'prix_calcule' => $menu->getPrix(),
            'prix_formate' => $this->formaterPrix($menu->getPrix())
        ];
    }

    public function comparerPrix(Menu $menu, float $prixIndividuel): array
    {
        $prixMenu = $menu->getPrix();
        $difference = $prixIndividuel - $prixMenu;
        
        return [
            'prix_menu' => $prixMenu,
            'prix_individuel' => $prixIndividuel,
            'difference' => $difference,
            'est_avantageux' => $difference > 0
        ];
    }
}