package ism.java.repository.impl;

import ism.java.config.DatabaseConfig;
import ism.java.entity.Produit;
import ism.java.enums.TypeComplement;
import ism.java.enums.TypeProduit;
import ism.java.repository.ProduitRepository;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class ProduitRepositoryImpl implements ProduitRepository {
    
    @Override
    public Produit insert(Produit produit) {
        
        String sql = "INSERT INTO produits (nom, prix, image, type_produit, type_complement, est_archive) " +
                     "VALUES (?, ?, ?, ?::type_produit, ?::type_complement, ?) RETURNING id";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setString(1, produit.getNom());
            stmt.setDouble(2, produit.getPrix());
            stmt.setString(3, produit.getImage());
            stmt.setString(4, produit.getTypeProduit().name());
            stmt.setString(5, produit.getTypeComplement() != null ? produit.getTypeComplement().name() : null);
            stmt.setBoolean(6, produit.isEstArchive());
            
            ResultSet rs = stmt.executeQuery();
            if (rs.next()) {
                produit.setId(rs.getInt("id"));
            }
            return produit;
            
        } catch (SQLException e) {
            System.err.println("Erreur insertion produit : " + e.getMessage());
            return null;
        }
    }
    
    @Override
    public Produit selectById(int id) {
        String sql = "SELECT * FROM produits WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setInt(1, id);
            ResultSet rs = stmt.executeQuery();
            
            if (rs.next()) {
                return mapResultSetToProduit(rs);
            }
            
        } catch (SQLException e) {
            System.err.println("Erreur recherche produit : " + e.getMessage());
        }
        return null;
    }
    
    @Override
    public List<Produit> selectAll() {
        String sql = "SELECT * FROM produits WHERE est_archive = FALSE ORDER BY type_produit, nom";
        return executeSelectQuery(sql);
    }
    
    @Override
    public List<Produit> selectByType(TypeProduit typeProduit) {
        String sql = "SELECT * FROM produits WHERE type_produit = ?::type_produit AND est_archive = FALSE ORDER BY nom";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setString(1, typeProduit.name());
            return executeResultSet(stmt.executeQuery());
            
        } catch (SQLException e) {
            System.err.println("Erreur recherche par type : " + e.getMessage());
        }
        return new ArrayList<>();
    }
    
    @Override
    public List<Produit> selectAllBurgers() {
        String sql = "SELECT * FROM produits WHERE type_produit = 'BURGER' AND est_archive = FALSE ORDER BY nom";
        return executeSelectQuery(sql);
    }
    
    @Override
    public List<Produit> selectAllBurgersIncludingArchived() {
        String sql = "SELECT * FROM produits WHERE type_produit = 'BURGER' ORDER BY est_archive, nom";
        return executeSelectQuery(sql);
    }
    
    @Override
    public List<Produit> selectAllComplements() {
        String sql = "SELECT * FROM produits WHERE type_produit = 'COMPLEMENT' AND est_archive = FALSE ORDER BY type_complement, nom";
        return executeSelectQuery(sql);
    }
    
    @Override
    public List<Produit> selectAllComplementsIncludingArchived() {
        String sql = "SELECT * FROM produits WHERE type_produit = 'COMPLEMENT' ORDER BY est_archive, type_complement, nom";
        return executeSelectQuery(sql);
    }
    
    @Override
    public List<Produit> selectByTypeComplement(TypeComplement typeComplement) {
        String sql = "SELECT * FROM produits WHERE type_complement = ?::type_complement AND est_archive = FALSE ORDER BY nom";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setString(1, typeComplement.name());
            return executeResultSet(stmt.executeQuery());
            
        } catch (SQLException e) {
            System.err.println("Erreur recherche type complement : " + e.getMessage());
        }
        return new ArrayList<>();
    }
    
    @Override
    public boolean update(Produit produit) {
        String sql = "UPDATE produits SET nom = ?, prix = ?, image = ?, type_complement = ?::type_complement WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setString(1, produit.getNom());
            stmt.setDouble(2, produit.getPrix());
            stmt.setString(3, produit.getImage());
            stmt.setString(4, produit.getTypeComplement() != null ? produit.getTypeComplement().name() : null);
            stmt.setInt(5, produit.getId());
            
            return stmt.executeUpdate() > 0;
            
        } catch (SQLException e) {
            System.err.println("Erreur mise a jour produit : " + e.getMessage());
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
        String sql = "UPDATE produits SET est_archive = ? WHERE id = ?";
        
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
        String sql = "DELETE FROM produits WHERE id = ?";
        
        try (Connection conn = DatabaseConfig.getConnection();
             PreparedStatement stmt = conn.prepareStatement(sql)) {
            
            stmt.setInt(1, id);
            return stmt.executeUpdate() > 0;
            
        } catch (SQLException e) {
            System.err.println("Erreur suppression produit : " + e.getMessage());
            return false;
        }
    }
    
    private List<Produit> executeSelectQuery(String sql) {
        try (Connection conn = DatabaseConfig.getConnection();
             Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery(sql)) {
            
            return executeResultSet(rs);
            
        } catch (SQLException e) {
            System.err.println("Erreur execution requete : " + e.getMessage());
            return new ArrayList<>();
        }
    }
    
    private List<Produit> executeResultSet(ResultSet rs) throws SQLException {
        List<Produit> produits = new ArrayList<>();
        while (rs.next()) {
            produits.add(mapResultSetToProduit(rs));
        }
        return produits;
    }
    
    private Produit mapResultSetToProduit(ResultSet rs) throws SQLException {
        Produit produit = new Produit();
        produit.setId(rs.getInt("id"));
        produit.setNom(rs.getString("nom"));
        produit.setPrix(rs.getDouble("prix"));
        produit.setImage(rs.getString("image"));
        produit.setTypeProduit(TypeProduit.valueOf(rs.getString("type_produit")));
        
        String typeComp = rs.getString("type_complement");
        if (typeComp != null && !typeComp.isEmpty()) {
            produit.setTypeComplement(TypeComplement.valueOf(typeComp));
        }
        
        produit.setEstArchive(rs.getBoolean("est_archive"));
        return produit;
    }
}
