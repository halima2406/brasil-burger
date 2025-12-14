package ism.java;

import ism.java.config.DatabaseConfig;
import ism.java.controller.BurgerController;
import ism.java.controller.ComplementController;
import ism.java.controller.MenuController;
import ism.java.controller.LivreurController;
import ism.java.controller.ZoneController;
import ism.java.repository.ProduitRepository;
import ism.java.repository.MenuRepository;
import ism.java.repository.LivreurRepository;
import ism.java.repository.ZoneRepository;
import ism.java.repository.impl.ProduitRepositoryImpl;
import ism.java.repository.impl.MenuRepositoryImpl;
import ism.java.repository.impl.LivreurRepositoryImpl;
import ism.java.repository.impl.ZoneRepositoryImpl;
import ism.java.service.BurgerService;
import ism.java.service.ComplementService;
import ism.java.service.MenuService;
import ism.java.service.LivreurService;
import ism.java.service.ZoneService;
import ism.java.service.impl.BurgerServiceImpl;
import ism.java.service.impl.ComplementServiceImpl;
import ism.java.service.impl.MenuServiceImpl;
import ism.java.service.impl.LivreurServiceImpl;
import ism.java.service.impl.ZoneServiceImpl;

import java.util.Scanner;

public class Main {
    
    public static void main(String[] args) {
        
        Scanner scanner = new Scanner(System.in);
        
        if (!DatabaseConfig.testConnection()) {
            System.out.println("Erreur connexion base de donnees");
            return;
        }
        
        ProduitRepository produitRepository = new ProduitRepositoryImpl();
        MenuRepository menuRepository = new MenuRepositoryImpl(produitRepository);
        LivreurRepository livreurRepository = new LivreurRepositoryImpl();
        ZoneRepository zoneRepository = new ZoneRepositoryImpl();
        
        BurgerService burgerService = new BurgerServiceImpl(produitRepository);
        ComplementService complementService = new ComplementServiceImpl(produitRepository);
        MenuService menuService = new MenuServiceImpl(menuRepository, produitRepository);
        LivreurService livreurService = new LivreurServiceImpl(livreurRepository);
        ZoneService zoneService = new ZoneServiceImpl(zoneRepository);
        
        BurgerController burgerController = new BurgerController(burgerService, scanner);
        ComplementController complementController = new ComplementController(complementService, scanner);
        MenuController menuController = new MenuController(menuService, burgerService, complementService, scanner);
        LivreurController livreurController = new LivreurController(livreurService, scanner);
        ZoneController zoneController = new ZoneController(zoneService, scanner);
        
        boolean running = true;
        while (running) {
            
            System.out.println("1. Gerer les Burgers");
            System.out.println("2. Gerer les Menus");
            System.out.println("3. Gerer les Complements");
            System.out.println("4. Gerer les Livreurs");
            System.out.println("5. Gerer les Zones");
            System.out.println("0. Quitter");
           
            System.out.print("Choix : ");
            
            int choix = scanner.nextInt();
            
            switch (choix) {
                case 1:
                    burgerController.run();
                    break;
                case 2:
                    menuController.run();
                    break;
                case 3:
                    complementController.run();
                    break;
                case 4:
                    livreurController.run();
                    break;
                case 5:
                    zoneController.run();
                    break;
                case 0:
                    running = false;
                    System.out.println("Au revoir");
                    break;
                default:
                    System.out.println("Choix invalide");
            }
        }
        
        DatabaseConfig.closeConnection();
        scanner.close();
    }
}