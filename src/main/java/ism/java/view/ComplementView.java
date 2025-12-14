package ism.java.view;

import ism.java.entity.Produit;
import ism.java.enums.TypeComplement;
import ism.java.config.CloudinaryConfig;

import java.util.List;
import java.util.Scanner;

public class ComplementView {
    
    private Scanner scanner;
    
    public ComplementView(Scanner scanner) {
        this.scanner = scanner;
    }
    
    
    
    public void afficherMenu() {
        System.out.println("\n===== GESTION DES COMPLEMENTS =====");
        System.out.println("1. Lister les complements");
        System.out.println("2. Ajouter un complement");
        System.out.println("3. Modifier un complement");
        System.out.println("4. Archiver/Desarchiver");
        System.out.println("0. Retour");
        System.out.print("Choix : ");
    }
    
    public void afficherListe(List<Produit> complements) {
        if (complements.isEmpty()) {
            System.out.println("Aucun complement trouve");
            return;
        }
        System.out.println("\nID\tNom\t\t\tPrix\t\tType\t\tStatut");
        for (Produit c : complements) {
            String statut = c.isEstArchive() ? "Archive" : "Actif";
            String type = c.getTypeComplement() == TypeComplement.BOISSON ? "Boisson" : "Frite";
            System.out.printf("%d\t%-20s\t%.0f FCFA\t%s\t\t%s%n", c.getId(), c.getNom(), c.getPrix(), type, statut);
        }
    }
    
    public void afficherComplement(Produit complement) {
        if (complement == null) {
            System.out.println("Complement non trouve");
            return;
        }
        System.out.println("\nID : " + complement.getId());
        System.out.println("Nom : " + complement.getNom());
        System.out.println("Prix : " + complement.getPrix() + " FCFA");
        System.out.println("Type : " + complement.getTypeComplement());
        System.out.println("Image : " + complement.getImage());
        System.out.println("Statut : " + (complement.isEstArchive() ? "Archive" : "Actif"));
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
            String url = CloudinaryConfig.uploadComplementImage(path);
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
    
    public TypeComplement saisirTypeComplement() {
        System.out.println("Type : 1 = Boisson, 2 = Frite");
        System.out.print("Choix : ");
        int choix = scanner.nextInt();
        scanner.nextLine();
        if (choix == 1) {
            return TypeComplement.BOISSON;
        }
        return TypeComplement.FRITE;
    }
    
    public TypeComplement saisirTypeComplementOptional(TypeComplement valeurActuelle) {
        System.out.println("Type actuel : " + valeurActuelle);
        System.out.println("Type : 1 = Boisson, 2 = Frite, 0 = Garder");
        System.out.print("Choix : ");
        int choix = scanner.nextInt();
        scanner.nextLine();
        if (choix == 1) {
            return TypeComplement.BOISSON;
        } else if (choix == 2) {
            return TypeComplement.FRITE;
        }
        return valeurActuelle;
    }
}
