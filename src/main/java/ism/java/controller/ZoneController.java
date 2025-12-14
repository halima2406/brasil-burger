package ism.java.controller;

import ism.java.entity.Zone;
import ism.java.service.ZoneService;
import ism.java.view.ZoneView;

import java.util.List;
import java.util.Scanner;

public class ZoneController {
    
    private ZoneService zoneService;
    private ZoneView zoneView;
    
    public ZoneController(ZoneService zoneService, Scanner scanner) {
        this.zoneService = zoneService;
        this.zoneView = new ZoneView(scanner);
    }
    
    public void run() {
        boolean back = false;
        while (!back) {
            zoneView.afficherMenu();
            int choix = zoneView.saisirChoix();
            
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
        List<Zone> zones = zoneService.getAllZonesIncludingArchived();
        zoneView.afficherListe(zones);
    }
    
    private void ajouter() {
        String quartier = zoneView.saisirQuartier();
        double prix = zoneView.saisirPrix();
        
        Zone zone = zoneService.addZone(quartier, prix);
        
        if (zone != null) {
            zoneView.afficherSucces("Zone ajoutee avec ID : " + zone.getId());
        } else {
            zoneView.afficherErreur("Impossible d'ajouter la zone");
        }
    }
}