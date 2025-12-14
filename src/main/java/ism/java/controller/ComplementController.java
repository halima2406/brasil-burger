package ism.java.controller;

import ism.java.entity.Produit;
import ism.java.enums.TypeComplement;
import ism.java.service.ComplementService;
import ism.java.view.ComplementView;

import java.util.List;
import java.util.Scanner;

public class ComplementController {
    
    private ComplementService complementService;
    private ComplementView complementView;
  
    
    public ComplementController(ComplementService complementService, Scanner scanner) {
        this.complementService = complementService;
        this.complementView = new ComplementView(scanner);
    }
    
    public void run() {
        boolean back = false;
        while (!back) {
            complementView.afficherMenu();
            int choix = complementView.saisirChoix();
  
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
        List<Produit> complements = complementService.getAllComplementsIncludingArchived();
        complementView.afficherListe(complements);
    }
    
    private void ajouter() {
        String nom = complementView.saisirNom();
        double prix = complementView.saisirPrix();
        String image = complementView.saisirImage();
        TypeComplement type = complementView.saisirTypeComplement();
        
        Produit complement = complementService.addComplement(nom, prix, image, type);
        
        if (complement != null) {
            complementView.afficherSucces("Complement ajoute avec ID : " + complement.getId());
        } else {
            complementView.afficherErreur("Impossible d'ajouter le complement");
        }
    }
    
    private void modifier() {
        lister();
        int id = complementView.saisirId();
        Produit complement = complementService.getComplementById(id);
        
        if (complement == null) {
            complementView.afficherErreur("Complement non trouve");
            return;
        }
        
        complementView.afficherComplement(complement);
        
        String nom = complementView.saisirNomOptional(complement.getNom());
        double prix = complementView.saisirPrixOptional(complement.getPrix());
        String image = complementView.saisirImageOptional(complement.getImage());
        TypeComplement type = complementView.saisirTypeComplementOptional(complement.getTypeComplement());
        
        if (complementService.updateComplement(id, nom, prix, image, type)) {
            complementView.afficherSucces("Complement modifie");
        } else {
            complementView.afficherErreur("Impossible de modifier");
        }
    }
}