package ism.java.repository.impl;

import ism.java.entity.Livreur;
import ism.java.repository.LivreurRepository;
import ism.java.config.DatabaseConfig;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class LivreurRepositoryImpl implements LivreurRepository {
    
    @Override
    public Livreur insert(Livreur livreur) {
        String sql = "INSERT INTO livreur (nom, telephone, est_archive) VALUES (?, ?, ?) RETURNING id";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setString(1, livreur.getNom());
            stmt.setString(2, livreur.getTelephone());
            stmt.setBoolean(3, false);
            
            ResultSet rs = stmt.executeQuery();
            if (rs.next()) {
                livreur.setId(rs.getInt("id"));
                return livreur;
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return null;
    }
    
    @Override
    public List<Livreur> selectAll() {
        List<Livreur> livreurs = new ArrayList<>();
        String sql = "SELECT * FROM livreur WHERE est_archive = false";
        
        try (Connection conn = DatabaseConfig.getConnection();
             Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery(sql)) {
            
            while (rs.next()) {
                livreurs.add(mapResultSetToLivreur(rs));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return livreurs;
    }
    
    @Override
    public List<Livreur> selectAllIncludingArchived() {
        List<Livreur> livreurs = new ArrayList<>();
        String sql = "SELECT * FROM livreur";
        
        try (Connection conn = DatabaseConfig.getConnection();
             Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery(sql)) {
            
            while (rs.next()) {
                livreurs.add(mapResultSetToLivreur(rs));
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return livreurs;
    }
    
    @Override
    public Livreur selectById(int id) {
        String sql = "SELECT * FROM livreur WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setInt(1, id);
            ResultSet rs = stmt.executeQuery();
            
            if (rs.next()) {
                return mapResultSetToLivreur(rs);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return null;
    }
    
    @Override
    public boolean update(Livreur livreur) {
        String sql = "UPDATE livreur SET nom = ?, telephone = ? WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setString(1, livreur.getNom());
            stmt.setString(2, livreur.getTelephone());
            stmt.setInt(3, livreur.getId());
            
            return stmt.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return false;
    }
    
    @Override
    public boolean archive(int id) {
        String sql = "UPDATE livreur SET est_archive = true WHERE id = ?";
        
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
        String sql = "UPDATE livreur SET est_archive = false WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setInt(1, id);
            return stmt.executeUpdate() > 0;
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return false;
    }
    
    private Livreur mapResultSetToLivreur(ResultSet rs) throws SQLException {
        Livreur livreur = new Livreur();
        livreur.setId(rs.getInt("id"));
        livreur.setNom(rs.getString("nom"));
        livreur.setTelephone(rs.getString("telephone"));
        livreur.setEstArchive(rs.getBoolean("est_archive"));
        return livreur;
    }
}