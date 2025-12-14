package ism.java.service;

import ism.java.entity.Livreur;
import java.util.List;

public interface LivreurService {
    
    Livreur addLivreur(String nom, String telephone);
    
    List<Livreur> getAllLivreurs();
    
    List<Livreur> getAllLivreursIncludingArchived();
    
    Livreur getLivreurById(int id);
    
    boolean updateLivreur(int id, String nom, String telephone);
    
    boolean archiveLivreur(int id);
    
    boolean unarchiveLivreur(int id);
}