package ism.java.controller;

import ism.java.entity.Livreur;
import ism.java.service.LivreurService;
import ism.java.view.LivreurView;

import java.util.List;
import java.util.Scanner;

public class LivreurController {
    
    private LivreurService livreurService;
    private LivreurView livreurView;
    
    public LivreurController(LivreurService livreurService, Scanner scanner) {
        this.livreurService = livreurService;
        this.livreurView = new LivreurView(scanner);
    }
    
    public void run() {
        boolean back = false;
        while (!back) {
            livreurView.afficherMenu();
            int choix = livreurView.saisirChoix();
            
            switch (choix) {
                case 1:
                    lister();
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
        List<Livreur> livreurs = livreurService.getAllLivreursIncludingArchived();
        livreurView.afficherListe(livreurs);
    }
}