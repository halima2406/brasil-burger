package ism.java.repository.impl;

import ism.java.config.DatabaseConfig;
import ism.java.entity.Menu;
import ism.java.repository.MenuRepository;
import ism.java.repository.ProduitRepository;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class MenuRepositoryImpl implements MenuRepository {
    
    private ProduitRepository produitRepository;
    
    public MenuRepositoryImpl(ProduitRepository produitRepository) {
        this.produitRepository = produitRepository;
    }
    
    @Override
    public Menu insert(Menu menu) {
        String sql = "INSERT INTO menus (nom, image, burger_id, boisson_id, frite_id, est_archive) " +
                     "VALUES (?, ?, ?, ?, ?, ?) RETURNING id";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setString(1, menu.getNom());
            stmt.setString(2, menu.getImage());
            stmt.setInt(3, menu.getBurgerId());
            stmt.setInt(4, menu.getBoissonId());
            stmt.setInt(5, menu.getFriteId());
            stmt.setBoolean(6, menu.isEstArchive());
            
            ResultSet rs = stmt.executeQuery();
            if (rs.next()) {
                menu.setId(rs.getInt("id"));
            }
            
            loadMenuProducts(menu);
            return menu;
            
        } catch (SQLException e) {
            System.err.println("Erreur insertion menu : " + e.getMessage());
            return null;
        }
    }
    
    @Override
    public Menu selectById(int id) {
        String sql = "SELECT * FROM menus WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setInt(1, id);
            ResultSet rs = stmt.executeQuery();
            
            if (rs.next()) {
                Menu menu = mapResultSetToMenu(rs);
                loadMenuProducts(menu);
                return menu;
            }
            
        } catch (SQLException e) {
            System.err.println("Erreur recherche menu : " + e.getMessage());
        }
        return null;
    }
    
    @Override
    public List<Menu> selectAll() {
        String sql = "SELECT * FROM menus WHERE est_archive = FALSE ORDER BY nom";
        return executeSelectQuery(sql);
    }
    
    @Override
    public List<Menu> selectAllIncludingArchived() {
        String sql = "SELECT * FROM menus ORDER BY est_archive, nom";
        return executeSelectQuery(sql);
    }
    
    @Override
    public boolean update(Menu menu) {
        String sql = "UPDATE menus SET nom = ?, image = ?, burger_id = ?, boisson_id = ?, frite_id = ? WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setString(1, menu.getNom());
            stmt.setString(2, menu.getImage());
            stmt.setInt(3, menu.getBurgerId());
            stmt.setInt(4, menu.getBoissonId());
            stmt.setInt(5, menu.getFriteId());
            stmt.setInt(6, menu.getId());
            
            return stmt.executeUpdate() > 0;
            
        } catch (SQLException e) {
            System.err.println("Erreur mise a jour menu : " + e.getMessage());
            return false;
        }
    }
    
    @Override
    public boolean archive(int id) {
        return updateArchiveStatus(id, true);
    }
    
    @Override
    public boolean unarchive(int id) {
        return updateArchiveStatus(id, false);
    }
    
    private boolean updateArchiveStatus(int id, boolean archived) {
        String sql = "UPDATE menus SET est_archive = ? WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setBoolean(1, archived);
            stmt.setInt(2, id);
            return stmt.executeUpdate() > 0;
            
        } catch (SQLException e) {
            System.err.println("Erreur changement statut archive : " + e.getMessage());
            return false;
        }
    }
    
    @Override
    public boolean delete(int id) {
        String sql = "DELETE FROM menus WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setInt(1, id);
            return stmt.executeUpdate() > 0;
            
        } catch (SQLException e) {
            System.err.println("Erreur suppression menu : " + e.getMessage());
            return false;
        }
    }
    
    private List<Menu> executeSelectQuery(String sql) {
        List<Menu> menus = new ArrayList<>();
        
        try (Connection conn = DatabaseConfig.getConnection();
             Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery(sql)) {
            
            while (rs.next()) {
                Menu menu = mapResultSetToMenu(rs);
                loadMenuProducts(menu);
                menus.add(menu);
            }
            
        } catch (SQLException e) {
            System.err.println("Erreur execution requete : " + e.getMessage());
        }
        return menus;
    }
    
    private Menu mapResultSetToMenu(ResultSet rs) throws SQLException {
        Menu menu = new Menu();
        menu.setId(rs.getInt("id"));
        menu.setNom(rs.getString("nom"));
        menu.setImage(rs.getString("image"));
        menu.setBurgerId(rs.getInt("burger_id"));
        menu.setBoissonId(rs.getInt("boisson_id"));
        menu.setFriteId(rs.getInt("frite_id"));
        menu.setEstArchive(rs.getBoolean("est_archive"));
        return menu;
    }
    
    private void loadMenuProducts(Menu menu) {
        menu.setBurger(produitRepository.selectById(menu.getBurgerId()));
        menu.setBoisson(produitRepository.selectById(menu.getBoissonId()));
        menu.setFrite(produitRepository.selectById(menu.getFriteId()));
    }
}
