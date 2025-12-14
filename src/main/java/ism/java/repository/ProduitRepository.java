package ism.java.repository;

import ism.java.entity.Produit;
import ism.java.enums.TypeComplement;
import ism.java.enums.TypeProduit;

import java.util.List;

public interface ProduitRepository {
    
    Produit insert(Produit produit);
    
    Produit selectById(int id);
    
    List<Produit> selectAll();
    
    List<Produit> selectByType(TypeProduit typeProduit);
    
    List<Produit> selectAllBurgers();
    
    List<Produit> selectAllBurgersIncludingArchived();
    
    List<Produit> selectAllComplements();
    
    List<Produit> selectAllComplementsIncludingArchived();
    
    List<Produit> selectByTypeComplement(TypeComplement typeComplement);
    
    boolean update(Produit produit);
    
    boolean archive(int id);
    
    boolean unarchive(int id);
    
    boolean delete(int id);
}
