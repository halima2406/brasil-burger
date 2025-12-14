package ism.java.view;

import ism.java.entity.Menu;
import ism.java.entity.Produit;
import ism.java.config.CloudinaryConfig;

import java.util.List;
import java.util.Scanner;

public class MenuView {
    
    private Scanner scanner;
    
    public MenuView(Scanner scanner) {
        this.scanner = scanner;
    }
    
  
    
    public void afficherMenu() {
        System.out.println("\n===== GESTION DES MENUS =====");
        System.out.println("1. Lister les menus");
        System.out.println("2. Ajouter un menu");
        System.out.println("3. Modifier un menu");
        System.out.println("4. Archiver/Desarchiver");
        System.out.println("5. Voir details");
        System.out.println("0. Retour");
        System.out.print("Choix : ");
    }
    
    public void afficherListe(List<Menu> menus) {
        if (menus.isEmpty()) {
            System.out.println("Aucun menu trouve");
            return;
        }
        System.out.println("\nID\tNom\t\t\tPrix Total\tStatut");
        for (Menu m : menus) {
            String statut = m.isEstArchive() ? "Archive" : "Actif";
            System.out.printf("%d\t%-20s\t%.0f FCFA\t%s%n", m.getId(), m.getNom(), m.getPrix(), statut);
        }
    }
    
    public void afficherDetails(Menu menu) {
        if (menu == null) {
            System.out.println("Menu non trouve");
            return;
        }
        System.out.println("\nID : " + menu.getId());
        System.out.println("Nom : " + menu.getNom());
        System.out.println("Image : " + menu.getImage());
        System.out.println("Statut : " + (menu.isEstArchive() ? "Archive" : "Actif"));
        System.out.println("\nComposition :");
        if (menu.getBurger() != null) {
            System.out.printf("  Burger : %s (%.0f FCFA)%n", menu.getBurger().getNom(), menu.getBurger().getPrix());
        }
        if (menu.getBoisson() != null) {
            System.out.printf("  Boisson : %s (%.0f FCFA)%n", menu.getBoisson().getNom(), menu.getBoisson().getPrix());
        }
        if (menu.getFrite() != null) {
            System.out.printf("  Frites : %s (%.0f FCFA)%n", menu.getFrite().getNom(), menu.getFrite().getPrix());
        }
        System.out.println("\nPrix Total : " + menu.getPrix() + " FCFA");
    }
    
    public void afficherListeBurgers(List<Produit> burgers) {
        System.out.println("\nBurgers disponibles :");
        for (Produit b : burgers) {
            System.out.printf("  %d. %s (%.0f FCFA)%n", b.getId(), b.getNom(), b.getPrix());
        }
    }
    
    public void afficherListeBoissons(List<Produit> boissons) {
        System.out.println("\nBoissons disponibles :");
        for (Produit b : boissons) {
            System.out.printf("  %d. %s (%.0f FCFA)%n", b.getId(), b.getNom(), b.getPrix());
        }
    }
    
    public void afficherListeFrites(List<Produit> frites) {
        System.out.println("\nFrites disponibles :");
        for (Produit f : frites) {
            System.out.printf("  %d. %s (%.0f FCFA)%n", f.getId(), f.getNom(), f.getPrix());
        }
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
            String url = CloudinaryConfig.uploadMenuImage(path);
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
    
    public int saisirBurgerId() {
        System.out.print("ID burger : ");
        int id = scanner.nextInt();
        scanner.nextLine();
        return id;
    }
    
    public int saisirBurgerIdOptional(int valeurActuelle) {
        System.out.print("ID burger : ");
        int input = scanner.nextInt();
        scanner.nextLine();
        if (input == 0) {
            return valeurActuelle;
        }
        return input;
    }
    
    public int saisirBoissonId() {
        System.out.print("ID boisson : ");
        int id = scanner.nextInt();
        scanner.nextLine();
        return id;
    }
    
    public int saisirBoissonIdOptional(int valeurActuelle) {
        System.out.print("ID boisson : ");
        int input = scanner.nextInt();
        scanner.nextLine();
        if (input == 0) {
            return valeurActuelle;
        }
        return input;
    }
    
    public int saisirFriteId() {
        System.out.print("ID frites : ");
        int id = scanner.nextInt();
        scanner.nextLine();
        return id;
    }
    
    public int saisirFriteIdOptional(int valeurActuelle) {
        System.out.print("ID frites : ");
        int input = scanner.nextInt();
        scanner.nextLine();
        if (input == 0) {
            return valeurActuelle;
        }
        return input;
    }
}
