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
                case 3:
                    modifier();
                    break;
                case 4:
                    archiver();
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
    
    private void modifier() {
        lister();
        int id = menuView.saisirId();
        Menu menu = menuService.getMenuById(id);
        
        if (menu == null) {
            menuView.afficherErreur("Menu non trouve");
            return;
        }
        
        menuView.afficherDetails(menu);
        
        String nom = menuView.saisirNomOptional(menu.getNom());
        String image = menuView.saisirImageOptional(menu.getImage());
        
        List<Produit> burgers = burgerService.getAllBurgers();
        menuView.afficherListeBurgers(burgers);
        int burgerId = menuView.saisirBurgerIdOptional(menu.getBurgerId());
        
        List<Produit> boissons = complementService.getAllBoissons();
        menuView.afficherListeBoissons(boissons);
        int boissonId = menuView.saisirBoissonIdOptional(menu.getBoissonId());
        
        List<Produit> frites = complementService.getAllFrites();
        menuView.afficherListeFrites(frites);
        int friteId = menuView.saisirFriteIdOptional(menu.getFriteId());
        
        if (menuService.updateMenu(id, nom, image, burgerId, boissonId, friteId)) {
            menuView.afficherSucces("Menu modifie");
        } else {
            menuView.afficherErreur("Impossible de modifier");
        }
    }
    
    private void archiver() {
        lister();
        int id = menuView.saisirId();
        
        Menu menu = null;
        for (Menu m : menuService.getAllMenusIncludingArchived()) {
            if (m.getId() == id) {
                menu = m;
                break;
            }
        }
        
        if (menu == null) {
            menuView.afficherErreur("Menu non trouve");
            return;
        }
        
        if (menu.isEstArchive()) {
            if (menuService.unarchiveMenu(id)) {
                menuView.afficherSucces("Menu desarchive");
            } else {
                menuView.afficherErreur("Impossible de desarchiver");
            }
        } else {
            if (menuService.archiveMenu(id)) {
                menuView.afficherSucces("Menu archive");
            } else {
                menuView.afficherErreur("Impossible d'archiver");
            }
        }
    }
}