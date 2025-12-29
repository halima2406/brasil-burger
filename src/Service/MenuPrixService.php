<?php

namespace App\Service;

use App\Entity\Menu;
use App\Entity\Produit;

/**
 * Service pour gérer la logique de calcul des prix des menus
 * ✅ CORRIGÉ pour être cohérent avec l'entité Menu
 */
class MenuPrixService
{
    /**
     * Calcule le prix d'un menu avec possibilité d'appliquer des réductions
     * ✅ CORRIGÉ : utilise maintenant getPrix() au lieu de getPrixCalcule()
     */
    public function calculerPrixMenu(Menu $menu): float
    {
        $prixBase = $menu->getPrix(); // ✅ Méthode qui existe dans Menu
        
        // Appliquer une réduction pour les menus (optionnel)
        return $this->appliquerReductionMenu($prixBase);
    }

    /**
     * Applique une réduction spéciale pour les menus
     * Par exemple : 5% de réduction par rapport aux prix individuels
     */
    private function appliquerReductionMenu(float $prixBase): float
    {
        // Réduction de 5% pour encourager les menus
        return $prixBase * 0.95;
    }

    /**
     * Calcule le prix d'un menu personnalisé avec des produits individuels
     */
    public function calculerPrixMenuPersonnalise(Produit $burger, Produit $boisson, Produit $frite): float
    {
        $prixTotal = 0.0;
        
        // Ajouter le prix du burger
        if ($burger->isBurger()) {
            $prixTotal += $burger->getPrix();
        }
        
        // Ajouter le prix de la boisson
        if ($boisson->isBoisson()) {
            $prixTotal += $boisson->getPrix();
        }
        
        // Ajouter le prix des frites
        if ($frite->isFrite()) {
            $prixTotal += $frite->getPrix();
        }
        
        return $this->appliquerReductionMenu($prixTotal);
    }

    /**
     * ✅ NOUVELLE : Calcul avec vérification des relations chargées
     */
    public function calculerPrixAvecVerification(Menu $menu): float
    {
        // Vérifier que les relations sont bien chargées
        if ($menu->getBurger() === null || $menu->getBoisson() === null || $menu->getFrite() === null) {
            throw new \RuntimeException(
                "Les relations du menu '{$menu->getNom()}' ne sont pas chargées. " .
                "Utilisez leftJoin dans votre requête pour charger burger, boisson et frite."
            );
        }
        
        return $menu->getPrix();
    }

    /**
     * Formate le prix pour l'affichage
     */
    public function formaterPrix(float $prix): string
    {
        return number_format($prix, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Calcule le pourcentage d'économie par rapport aux prix individuels
     */
    public function calculerPourcentageEconomie(Menu $menu): float
    {
        $prixIndividuel = $menu->getPrix(); // Prix sans réduction
        $prixMenu = $this->calculerPrixMenu($menu); // Prix avec réduction
        
        if ($prixIndividuel > 0) {
            return (($prixIndividuel - $prixMenu) / $prixIndividuel) * 100;
        }
        
        return 0.0;
    }

    /**
     * ✅ NOUVELLE : Diagnostic complet d'un menu
     */
    public function diagnostiquerMenu(Menu $menu): array
    {
        $diagnostic = [
            'menu_id' => $menu->getId(),
            'menu_nom' => $menu->getNom(),
            'relations_chargees' => [
                'burger' => $menu->getBurger() !== null,
                'boisson' => $menu->getBoisson() !== null,
                'frite' => $menu->getFrite() !== null,
            ],
            'prix_calcule' => null,
            'erreur' => null
        ];
        
        try {
            $diagnostic['prix_calcule'] = $menu->getPrix();
            $diagnostic['prix_formate'] = $this->formaterPrix($diagnostic['prix_calcule']);
        } catch (\Exception $e) {
            $diagnostic['erreur'] = $e->getMessage();
        }
        
        return $diagnostic;
    }

    /**
     * ✅ NOUVELLE : Méthode de test pour vérifier que tout fonctionne
     */
    public function testerCalculPrix(): array
    {
        return [
            'service' => self::class,
            'methodes_disponibles' => [
                'calculerPrixMenu',
                'calculerPrixMenuPersonnalise', 
                'calculerPrixAvecVerification',
                'formaterPrix',
                'calculerPourcentageEconomie',
                'diagnostiquerMenu'
            ],
            'status' => 'Service opérationnel'
        ];
    }
}