package ism.java.view;

import ism.java.entity.Produit;
import ism.java.config.CloudinaryConfig;

import java.util.List;
import java.util.Scanner;

public class BurgerView {
    
    private Scanner scanner;
    
    public BurgerView(Scanner scanner) {
        this.scanner = scanner;
    }
    
    
    
    public void afficherMenu() {
        System.out.println("\n===== GESTION DES BURGERS =====");
        System.out.println("1. Lister les burgers");
        System.out.println("2. Ajouter un burger");
        System.out.println("3. Modifier un burger");
        System.out.println("4. Archiver/Desarchiver");
        System.out.println("0. Retour");
        System.out.print("Choix : ");
    }
    
    public void afficherListe(List<Produit> burgers) {
        if (burgers.isEmpty()) {
            System.out.println("Aucun burger trouve");
            return;
        }
        System.out.println("\nID\tNom\t\t\tPrix\t\tStatut");
        for (Produit b : burgers) {
            String statut = b.isEstArchive() ? "Archive" : "Actif";
            System.out.printf("%d\t%-20s\t%.0f FCFA\t%s%n", b.getId(), b.getNom(), b.getPrix(), statut);
        }
    }
    
    public void afficherBurger(Produit burger) {
        if (burger == null) {
            System.out.println("Burger non trouve");
            return;
        }
        System.out.println("\nID : " + burger.getId());
        System.out.println("Nom : " + burger.getNom());
        System.out.println("Prix : " + burger.getPrix() + " FCFA");
        System.out.println("Image : " + burger.getImage());
        System.out.println("Statut : " + (burger.isEstArchive() ? "Archive" : "Actif"));
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
        System.out.print("Nom : ");
        String input = scanner.nextLine();
        if (input.isEmpty()) {
            return valeurActuelle;
        }
        return input;
    }
    
    public double saisirPrix() {
        System.out.print("Prix : ");
        double prix = scanner.nextDouble();
        scanner.nextLine();
        return prix;
    }
    
    public double saisirPrixOptional(double valeurActuelle) {
        System.out.print("Prix : ");
        double input = scanner.nextDouble();
        scanner.nextLine();
        if (input == 0) {
            return valeurActuelle;
        }
        return input;
    }
    
    public String saisirImage() {
        System.out.println("Image : ");
        System.out.println("  1. Entrer une URL");
        System.out.println("  2. Uploader un fichier local");
        System.out.println("  0. Ignorer");
        System.out.print("Choix : ");
        int choix = scanner.nextInt();
        scanner.nextLine();
        
        if (choix == 1) {
            System.out.print("URL : ");
            return scanner.nextLine();
        } else if (choix == 2) {
            System.out.print("Chemin du fichier : ");
            String path = scanner.nextLine();
            String url = CloudinaryConfig.uploadBurgerImage(path);
            if (url != null) {
                System.out.println("Image uploadee : " + url);
            }
            return url;
        }
        return null;
    }
    
    public String saisirImageOptional(String valeurActuelle) {
        System.out.print("Image : ");
        String input = scanner.nextLine();
        if (input.isEmpty()) {
            return valeurActuelle;
        }
        return input;
    }
}
