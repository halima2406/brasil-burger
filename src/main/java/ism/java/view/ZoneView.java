package ism.java.view;

import ism.java.entity.Zone;

import java.util.List;
import java.util.Scanner;

public class ZoneView {
    
    private Scanner scanner;
    
    public ZoneView(Scanner scanner) {
        this.scanner = scanner;
    }
    
    public void afficherMenu() {
        System.out.println("\n===== GESTION DES ZONES =====");
        System.out.println("1. Lister les zones");
        System.out.println("2. Ajouter une zone");
        System.out.println("3. Modifier une zone");
        System.out.println("4. Archiver/Desarchiver");
        System.out.println("0. Retour");
        System.out.print("Choix : ");
    }
    
    public void afficherListe(List<Zone> zones) {
        if (zones.isEmpty()) {
            System.out.println("Aucune zone trouvee");
            return;
        }
        System.out.println("\nID\tQuartier\t\tPrix\t\tStatut");
        for (Zone z : zones) {
            String statut = z.isEstArchive() ? "Archive" : "Actif";
            System.out.printf("%d\t%-20s\t%.0f FCFA\t\t%s%n", z.getId(), z.getQuartier(), z.getPrix(), statut);
        }
    }
    
    public void afficherZone(Zone zone) {
        if (zone == null) {
            System.out.println("Zone non trouvee");
            return;
        }
        System.out.println("\nID : " + zone.getId());
        System.out.println("Quartier : " + zone.getQuartier());
        System.out.println("Prix : " + zone.getPrix() + " FCFA");
        System.out.println("Statut : " + (zone.isEstArchive() ? "Archive" : "Actif"));
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
    
    public String saisirQuartier() {
        System.out.print("Quartier : ");
        return scanner.nextLine();
    }
    
    public String saisirQuartierOptional(String valeurActuelle) {
        System.out.print("Quartier (" + valeurActuelle + ") : ");
        String input = scanner.nextLine();
        if (input.isEmpty()) {
            return valeurActuelle;
        }
        return input;
    }
    
    public double saisirPrix() {
        System.out.print("Prix livraison : ");
        double prix = scanner.nextDouble();
        scanner.nextLine();
        return prix;
    }
    
    public double saisirPrixOptional(double valeurActuelle) {
        System.out.print("Prix livraison (" + valeurActuelle + ") : ");
        double input = scanner.nextDouble();
        scanner.nextLine();
        if (input == 0) {
            return valeurActuelle;
        }
        return input;
    }
}