package ism.java.entity;

public class Livreur {
    
    private int id;
    private String nom;
    private String telephone;
    private boolean estArchive;
    
    public Livreur() {
    }
    
    public Livreur(String nom, String telephone) {
        this.nom = nom;
        this.telephone = telephone;
        this.estArchive = false;
    }
    
    // Getters et Setters
    public int getId() {
        return id;
    }
    
    public void setId(int id) {
        this.id = id;
    }
    
    public String getNom() {
        return nom;
    }
    
    public void setNom(String nom) {
        this.nom = nom;
    }
    
    public String getTelephone() {
        return telephone;
    }
    
    public void setTelephone(String telephone) {
        this.telephone = telephone;
    }
    
    public boolean isEstArchive() {
        return estArchive;
    }
    
    public void setEstArchive(boolean estArchive) {
        this.estArchive = estArchive;
    }
}