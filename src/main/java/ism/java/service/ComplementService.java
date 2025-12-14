package ism.java.service;

import ism.java.entity.Produit;
import ism.java.enums.TypeComplement;

import java.util.List;

public interface ComplementService {
    
    Produit addComplement(String nom, double prix, String image, TypeComplement typeComplement);
    
    List<Produit> getAllComplements();
    
    List<Produit> getAllComplementsIncludingArchived();
    
    List<Produit> getAllBoissons();
    
    List<Produit> getAllFrites();
    
    Produit getComplementById(int id);
    
    boolean updateComplement(int id, String nom, double prix, String image, TypeComplement typeComplement);
    
    boolean archiveComplement(int id);
    
    boolean unarchiveComplement(int id);
}
