package ism.java.entity;

import ism.java.enums.TypeProduit;
import ism.java.enums.TypeComplement;

public class Produit {
    
    private int id;
    private String nom;
    private double prix;
    private String image;
    private TypeProduit typeProduit;
    private TypeComplement typeComplement;
    private boolean estArchive;
    
    public Produit() {
        this.estArchive = false;
    }
    
    public Produit(int id, String nom, double prix, String image, 
                   TypeProduit typeProduit, TypeComplement typeComplement, boolean estArchive) {
        this.id = id;
        this.nom = nom;
        this.prix = prix;
        this.image = image;
        this.typeProduit = typeProduit;
        this.typeComplement = typeComplement;
        this.estArchive = estArchive;
    }
   
    public Produit(String nom, double prix, String image) {
        this();
        this.nom = nom;
        this.prix = prix;
        this.image = image;
        this.typeProduit = TypeProduit.BURGER;
        this.typeComplement = null;
    }
    
    
    public Produit(String nom, double prix, String image, TypeComplement typeComplement) {
        this();
        this.nom = nom;
        this.prix = prix;
        this.image = image;
        this.typeProduit = TypeProduit.COMPLEMENT;
        this.typeComplement = typeComplement;
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
    
    public double getPrix() {
        return prix;
    }
    
    public void setPrix(double prix) {
        this.prix = prix;
    }
    
    public String getImage() {
        return image;
    }
    
    public void setImage(String image) {
        this.image = image;
    }
    
    public TypeProduit getTypeProduit() {
        return typeProduit;
    }
    
    public void setTypeProduit(TypeProduit typeProduit) {
        this.typeProduit = typeProduit;
    }
    
    public TypeComplement getTypeComplement() {
        return typeComplement;
    }
    
    public void setTypeComplement(TypeComplement typeComplement) {
        this.typeComplement = typeComplement;
    }
    
    public boolean isEstArchive() {
        return estArchive;
    }
    
    public void setEstArchive(boolean estArchive) {
        this.estArchive = estArchive;
    }
    
    public boolean isBurger() {
        return typeProduit == TypeProduit.BURGER;
    }
    
    public boolean isComplement() {
        return typeProduit == TypeProduit.COMPLEMENT;
    }
    
    public boolean isBoisson() {
        return isComplement() && typeComplement == TypeComplement.BOISSON;
    }
    
    public boolean isFrite() {
        return isComplement() && typeComplement == TypeComplement.FRITE;
    }
}
