package ism.java.service.impl;

import ism.java.entity.Produit;
import ism.java.enums.TypeComplement;
import ism.java.repository.ProduitRepository;
import ism.java.service.ComplementService;

import java.util.List;

public class ComplementServiceImpl implements ComplementService {
    
    private ProduitRepository produitRepository;
    
    public ComplementServiceImpl(ProduitRepository produitRepository) {
        this.produitRepository = produitRepository;
    }
    
    @Override
    public Produit addComplement(String nom, double prix, String image, TypeComplement typeComplement) {
        if (nom == null || nom.trim().isEmpty()) {
            return null;
        }
        if (prix <= 0) {
            return null;
        }
        if (typeComplement == null) {
            return null;
        }
        
        Produit complement = new Produit(nom.trim(), prix, image, typeComplement);
        return produitRepository.insert(complement);
    }
    
    @Override
    public List<Produit> getAllComplements() {
        return produitRepository.selectAllComplements();
    }
    
    @Override
    public List<Produit> getAllComplementsIncludingArchived() {
        return produitRepository.selectAllComplementsIncludingArchived();
    }
    
    @Override
    public List<Produit> getAllBoissons() {
        return produitRepository.selectByTypeComplement(TypeComplement.BOISSON);
    }
    
    @Override
    public List<Produit> getAllFrites() {
        return produitRepository.selectByTypeComplement(TypeComplement.FRITE);
    }
    
    @Override
    public Produit getComplementById(int id) {
        Produit produit = produitRepository.selectById(id);
        if (produit != null && produit.isComplement()) {
            return produit;
        }
        return null;
    }
    
    @Override
    public boolean updateComplement(int id, String nom, double prix, String image, TypeComplement typeComplement) {
        if (nom == null || nom.trim().isEmpty()) {
            return false;
        }
        if (prix <= 0) {
            return false;
        }
        if (typeComplement == null) {
            return false;
        }
        
        Produit complement = getComplementById(id);
        if (complement == null) {
            return false;
        }
        
        complement.setNom(nom.trim());
        complement.setPrix(prix);
        complement.setImage(image);
        complement.setTypeComplement(typeComplement);
        
        return produitRepository.update(complement);
    }
    
    @Override
    public boolean archiveComplement(int id) {
        Produit complement = produitRepository.selectById(id);
        if (complement == null || !complement.isComplement()) {
            return false;
        }
        return produitRepository.archive(id);
    }
    
    @Override
    public boolean unarchiveComplement(int id) {
        Produit complement = produitRepository.selectById(id);
        if (complement == null || !complement.isComplement()) {
            return false;
        }
        return produitRepository.unarchive(id);
    }
}
