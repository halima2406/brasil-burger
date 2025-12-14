package ism.java.controller;

import ism.java.entity.Menu;
import ism.java.entity.Produit;
import ism.java.service.BurgerService;
import ism.java.service.ComplementService;
import ism.java.service.MenuService;
import ism.java.view.MenuView;

import java.util.List;
import java.util.Scanner;

public class MenuController {
    
    private MenuService menuService;
    private BurgerService burgerService;
    private ComplementService complementService;
    private MenuView menuView;
   
    
    public MenuController(MenuService menuService, BurgerService burgerService, 
                          ComplementService complementService, Scanner scanner) {
        this.menuService = menuService;
        this.burgerService = burgerService;
        this.complementService = complementService;
        this.menuView = new MenuView(scanner);
    }
    
    public void run() {
        boolean back = false;
        while (!back) {
            menuView.afficherMenu();
            int choix = menuView.saisirChoix();
  
            switch (choix) {
                case 1:
                    lister();
                    break;
                case 2:
                    ajouter();
                    break;
                case 0:
                    back = true;
                    break;
                default:
                    menuView.afficherErreur("Choix invalide");
            }
        }
    }
    
    private void lister() {
        List<Menu> menus = menuService.getAllMenusIncludingArchived();
        menuView.afficherListe(menus);
    }
    
    private void ajouter() {
        String nom = menuView.saisirNom();
        String image = menuView.saisirImage();
        
        List<Produit> burgers = burgerService.getAllBurgers();
        menuView.afficherListeBurgers(burgers);
        int burgerId = menuView.saisirBurgerId();
        
        List<Produit> boissons = complementService.getAllBoissons();
        menuView.afficherListeBoissons(boissons);
        int boissonId = menuView.saisirBoissonId();
        
        List<Produit> frites = complementService.getAllFrites();
        menuView.afficherListeFrites(frites);
        int friteId = menuView.saisirFriteId();
        
        Menu menu = menuService.addMenu(nom, image, burgerId, boissonId, friteId);
        
        if (menu != null) {
            menuView.afficherSucces("Menu ajoute avec ID : " + menu.getId());
            menuView.afficherDetails(menu);
        } else {
            menuView.afficherErreur("Impossible d'ajouter le menu");
        }
    }
}