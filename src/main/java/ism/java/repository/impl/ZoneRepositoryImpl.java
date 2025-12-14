package ism.java.repository.impl;

import ism.java.entity.Zone;
import ism.java.repository.ZoneRepository;
import ism.java.config.DatabaseConfig;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class ZoneRepositoryImpl implements ZoneRepository {
    
    @Override
    public Zone insert(Zone zone) {
        String sql = "INSERT INTO zone (quartier, prix, est_archive) VALUES (?, ?, ?) RETURNING id";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setString(1, zone.getQuartier());
            stmt.setDouble(2, zone.getPrix());
            stmt.setBoolean(3, false);
            
            ResultSet rs = stmt.executeQuery();
            if (rs.next()) {
                zone.setId(rs.getInt("id"));
                return zone;
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return null;
    }
    
    @Override
    public List<Zone> selectAll() {
        List<Zone> zones = new ArrayList<>();
        String sql = "SELECT * FROM zone WHERE est_archive = false";
        
        try (Connection conn = DatabaseConfig.getConnection();
             Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery(sql)) {
            
            while (rs.next()) {
                zones.add(mapResultSetToZone(rs));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return zones;
    }
    
    @Override
    public List<Zone> selectAllIncludingArchived() {
        List<Zone> zones = new ArrayList<>();
        String sql = "SELECT * FROM zone";
        
        try (Connection conn = DatabaseConfig.getConnection();
             Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery(sql)) {
            
            while (rs.next()) {
                zones.add(mapResultSetToZone(rs));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return zones;
    }
    
    @Override
    public Zone selectById(int id) {
        String sql = "SELECT * FROM zone WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setInt(1, id);
            ResultSet rs = stmt.executeQuery();
            
            if (rs.next()) {
                return mapResultSetToZone(rs);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return null;
    }
    
    @Override
    public boolean update(Zone zone) {
        String sql = "UPDATE zone SET quartier = ?, prix = ? WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setString(1, zone.getQuartier());
            stmt.setDouble(2, zone.getPrix());
            stmt.setInt(3, zone.getId());
            
            return stmt.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return false;
    }
    
    @Override
    public boolean archive(int id) {
        String sql = "UPDATE zone SET est_archive = true WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setInt(1, id);
            return stmt.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return false;
    }
    
    @Override
    public boolean unarchive(int id) {
        String sql = "UPDATE zone SET est_archive = false WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setInt(1, id);
            return stmt.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return false;
    }
    
    private Zone mapResultSetToZone(ResultSet rs) throws SQLException {
        Zone zone = new Zone();
        zone.setId(rs.getInt("id"));
        zone.setQuartier(rs.getString("quartier"));
        zone.setPrix(rs.getDouble("prix"));
        zone.setEstArchive(rs.getBoolean("est_archive"));
        return zone;
    }
}