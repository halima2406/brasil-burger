package ism.java.service.impl;

import ism.java.entity.Zone;
import ism.java.repository.ZoneRepository;
import ism.java.service.ZoneService;

import java.util.List;

public class ZoneServiceImpl implements ZoneService {
    
    private ZoneRepository zoneRepository;
    
    public ZoneServiceImpl(ZoneRepository zoneRepository) {
        this.zoneRepository = zoneRepository;
    }
    
    @Override
    public Zone addZone(String quartier, double prix) {
        if (quartier == null || quartier.trim().isEmpty()) {
            return null;
        }
        if (prix < 0) {
            return null;
        }
        
        Zone zone = new Zone(quartier.trim(), prix);
        return zoneRepository.insert(zone);
    }
    
    @Override
    public List<Zone> getAllZones() {
        return zoneRepository.selectAll();
    }
    
    @Override
    public List<Zone> getAllZonesIncludingArchived() {
        return zoneRepository.selectAllIncludingArchived();
    }
    
    @Override
    public Zone getZoneById(int id) {
        return zoneRepository.selectById(id);
    }
    
    @Override
    public boolean updateZone(int id, String quartier, double prix) {
        if (quartier == null || quartier.trim().isEmpty()) {
            return false;
        }
        if (prix < 0) {
            return false;
        }
        
        Zone zone = getZoneById(id);
        if (zone == null) {
            return false;
        }
        
        zone.setQuartier(quartier.trim());
        zone.setPrix(prix);
        
        return zoneRepository.update(zone);
    }
    
    @Override
    public boolean archiveZone(int id) {
        Zone zone = zoneRepository.selectById(id);
        if (zone == null) {
            return false;
        }
        return zoneRepository.archive(id);
    }
    
    @Override
    public boolean unarchiveZone(int id) {
        Zone zone = zoneRepository.selectById(id);
        if (zone == null) {
            return false;
        }
        return zoneRepository.unarchive(id);
    }
}