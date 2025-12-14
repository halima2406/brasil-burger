package ism.java.repository;

import ism.java.entity.Zone;
import java.util.List;

public interface ZoneRepository {
    
    Zone insert(Zone zone);
    
    List<Zone> selectAll();
    
    List<Zone> selectAllIncludingArchived();
    
    Zone selectById(int id);
    
    boolean update(Zone zone);
    
    boolean archive(int id);
    
    boolean unarchive(int id);
}