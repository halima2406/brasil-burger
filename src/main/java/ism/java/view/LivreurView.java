package ism.java.view;

import ism.java.entity.Livreur;

import java.util.List;
import java.util.Scanner;

public class LivreurView {
    
    private Scanner scanner;
    
    public LivreurView(Scanner scanner) {
        this.scanner = scanner;
    }
    
    public void afficherMenu() {
        System.out.println("\n===== GESTION DES LIVREURS =====");
        System.out.println("1. Lister les livreurs");
        System.out.println("2. Ajouter un livreur");
        System.out.println("3. Modifier un livreur");
        System.out.println("4. Archiver/Desarchiver");
        System.out.println("0. Retour");
        System.out.print("Choix : ");
    }
    
    public void afficherListe(List<Livreur> livreurs) {
        if (livreurs.isEmpty()) {
            System.out.println("Aucun livreur trouve");
            return;
        }
        System.out.println("\nID\tNom\t\t\tTelephone\tStatut");
        for (Livreur l : livreurs) {
            String statut = l.isEstArchive() ? "Archive" : "Actif";
            System.out.printf("%d\t%-20s\t%s\t\t%s%n", l.getId(), l.getNom(), l.getTelephone(), statut);
        }
    }
    
    public void afficherLivreur(Livreur livreur) {
        if (livreur == null) {
            System.out.println("Livreur non trouve");
            return;
        }
        System.out.println("\nID : " + livreur.getId());
        System.out.println("Nom : " + livreur.getNom());
        System.out.println("Telephone : " + livreur.getTelephone());
        System.out.println("Statut : " + (livreur.isEstArchive() ? "Archive" : "Actif"));
    }
    
    public void afficherSucces(String message) {
        System.out.println("Succes : " + message);
    }
    
    public void afficherErreur(String message) {
        System.out.println("Erreur : " + message);
    }
    
    public int saisirChoix() {
        int choix = scanner.nextInt();
        scanner.nextLine();
        return choix;
    }
    
    public int saisirId() {
        System.out.print("ID : ");
        int id = scanner.nextInt();
        scanner.nextLine();
        return id;
    }
    
    public String saisirNom() {
        System.out.print("Nom : ");
        return scanner.nextLine();
    }
    
    public String saisirNomOptional(String valeurActuelle) {
        System.out.print("Nom (" + valeurActuelle + ") : ");
        String input = scanner.nextLine();
        if (input.isEmpty()) {
            return valeurActuelle;
        }
        return input;
    }
    
    public String saisirTelephone() {
        System.out.print("Telephone : ");
        return scanner.nextLine();
    }
    
    public String saisirTelephoneOptional(String valeurActuelle) {
        System.out.print("Telephone (" + valeurActuelle + ") : ");
        String input = scanner.nextLine();
        if (input.isEmpty()) {
            return valeurActuelle;
        }
        return input;
    }
}