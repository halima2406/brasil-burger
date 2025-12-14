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
                case 2:
                    ajouter();
                    break;
                case 3:
                    modifier();
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
    
    private void ajouter() {
        String nom = livreurView.saisirNom();
        String telephone = livreurView.saisirTelephone();
        
        Livreur livreur = livreurService.addLivreur(nom, telephone);
        
        if (livreur != null) {
            livreurView.afficherSucces("Livreur ajoute avec ID : " + livreur.getId());
        } else {
            livreurView.afficherErreur("Impossible d'ajouter le livreur");
        }
    }
    
    private void modifier() {
        lister();
        int id = livreurView.saisirId();
        Livreur livreur = livreurService.getLivreurById(id);
        
        if (livreur == null) {
            livreurView.afficherErreur("Livreur non trouve");
            return;
        }
        
        livreurView.afficherLivreur(livreur);
        
        String nom = livreurView.saisirNomOptional(livreur.getNom());
        String telephone = livreurView.saisirTelephoneOptional(livreur.getTelephone());
        
        if (livreurService.updateLivreur(id, nom, telephone)) {
            livreurView.afficherSucces("Livreur modifie");
        } else {
            livreurView.afficherErreur("Impossible de modifier");
        }
    }
}