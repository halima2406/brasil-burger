package ism.java.service;

import ism.java.entity.Zone;
import java.util.List;

public interface ZoneService {
    
    Zone addZone(String quartier, double prix);
    
    List<Zone> getAllZones();
    
    List<Zone> getAllZonesIncludingArchived();
    
    Zone getZoneById(int id);
    
    boolean updateZone(int id, String quartier, double prix);
    
    boolean archiveZone(int id);
    
    boolean unarchiveZone(int id);
}