package ism.java.repository;

import ism.java.entity.Livreur;
import java.util.List;

public interface LivreurRepository {
    
    Livreur insert(Livreur livreur);
    
    List<Livreur> selectAll();
    
    List<Livreur> selectAllIncludingArchived();
    
    Livreur selectById(int id);
    
    boolean update(Livreur livreur);
    
    boolean archive(int id);
    
    boolean unarchive(int id);
}