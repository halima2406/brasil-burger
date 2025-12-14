package ism.java.service;

import ism.java.entity.Produit;

import java.util.List;

public interface BurgerService {
    
    Produit addBurger(String nom, double prix, String image);
    
    List<Produit> getAllBurgers();
    
    List<Produit> getAllBurgersIncludingArchived();
    
    Produit getBurgerById(int id);
    
    boolean updateBurger(int id, String nom, double prix, String image);
    
    boolean archiveBurger(int id);
    
    boolean unarchiveBurger(int id);
}
