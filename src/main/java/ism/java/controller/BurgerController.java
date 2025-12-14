package ism.java.controller;

import ism.java.entity.Produit;
import ism.java.service.BurgerService;
import ism.java.view.BurgerView;

import java.util.List;
import java.util.Scanner;

public class BurgerController {
    
    private BurgerService burgerService;
    private BurgerView burgerView;
    
    public BurgerController(BurgerService burgerService, Scanner scanner) {
        this.burgerService = burgerService;
        this.burgerView = new BurgerView(scanner);
    }
    
    public void run() {
        boolean back = false;
        while (!back) {
            burgerView.afficherMenu();
            int choix = burgerView.saisirChoix();
            
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
                    System.out.println("Choix invalide");
            }
        }
    }
    
    private void lister() {
        List<Produit> burgers = burgerService.getAllBurgersIncludingArchived();
        burgerView.afficherListe(burgers);
    }
    
    private void ajouter() {
        String nom = burgerView.saisirNom();
        double prix = burgerView.saisirPrix();
        String image = burgerView.saisirImage();
        
        Produit burger = burgerService.addBurger(nom, prix, image);
        
        if (burger != null) {
            burgerView.afficherSucces("Burger ajoute avec ID : " + burger.getId());
        } else {
            burgerView.afficherErreur("Impossible d'ajouter le burger");
        }
    }
}