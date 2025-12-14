package ism.java.service.impl;

import ism.java.entity.Produit;
import ism.java.enums.TypeProduit;
import ism.java.repository.ProduitRepository;
import ism.java.service.BurgerService;

import java.util.List;

public class BurgerServiceImpl implements BurgerService {
    
    private ProduitRepository produitRepository;
    
    public BurgerServiceImpl(ProduitRepository produitRepository) {
        this.produitRepository = produitRepository;
    }
    
    @Override
    public Produit addBurger(String nom, double prix, String image) {
        if (nom == null || nom.trim().isEmpty()) {
            return null;
        }
        if (prix <= 0) {
            return null;
        }
        
        Produit burger = new Produit(nom.trim(), prix, image);
        burger.setTypeProduit(TypeProduit.BURGER);
        
        return produitRepository.insert(burger);
    }
    
    @Override
    public List<Produit> getAllBurgers() {
        return produitRepository.selectAllBurgers();
    }
    
    @Override
    public List<Produit> getAllBurgersIncludingArchived() {
        return produitRepository.selectAllBurgersIncludingArchived();
    }
    
    @Override
    public Produit getBurgerById(int id) {
        Produit produit = produitRepository.selectById(id);
        if (produit != null && produit.isBurger()) {
            return produit;
        }
        return null;
    }
    
    @Override
    public boolean updateBurger(int id, String nom, double prix, String image) {
        if (nom == null || nom.trim().isEmpty()) {
            return false;
        }
        if (prix <= 0) {
            return false;
        }
        
        Produit burger = getBurgerById(id);
        if (burger == null) {
            return false;
        }
        
        burger.setNom(nom.trim());
        burger.setPrix(prix);
        burger.setImage(image);
        
        return produitRepository.update(burger);
    }
    
    @Override
    public boolean archiveBurger(int id) {
        Produit burger = produitRepository.selectById(id);
        if (burger == null || !burger.isBurger()) {
            return false;
        }
        return produitRepository.archive(id);
    }
    
    @Override
    public boolean unarchiveBurger(int id) {
        Produit burger = produitRepository.selectById(id);
        if (burger == null || !burger.isBurger()) {
            return false;
        }
        return produitRepository.unarchive(id);
    }
}
