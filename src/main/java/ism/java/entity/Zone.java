package ism.java.entity;

public class Zone {
    
    private int id;
    private String quartier;
    private double prix;
    private boolean estArchive;
    
    public Zone() {
    }
    
    public Zone(String quartier, double prix) {
        this.quartier = quartier;
        this.prix = prix;
        this.estArchive = false;
    }
    
   
    public int getId() {
        return id;
    }
    
    public void setId(int id) {
        this.id = id;
    }
    
    public String getQuartier() {
        return quartier;
    }
    
    public void setQuartier(String quartier) {
        this.quartier = quartier;
    }
    
    public double getPrix() {
        return prix;
    }
    
    public void setPrix(double prix) {
        this.prix = prix;
    }
    
    public boolean isEstArchive() {
        return estArchive;
    }
    
    public void setEstArchive(boolean estArchive) {
        this.estArchive = estArchive;
    }
}