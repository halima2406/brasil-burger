package ism.java.entity;

public class Menu {
    
    private int id;
    private String nom;
    private String image;
    private int burgerId;
    private int boissonId;
    private int friteId;
    private boolean estArchive;
    
    private Produit burger;
    private Produit boisson;
    private Produit frite;
    
    public Menu() {
        this.estArchive = false;
    }
    
    public Menu(int id, String nom, String image, int burgerId, int boissonId, int friteId, boolean estArchive) {
        this.id = id;
        this.nom = nom;
        this.image = image;
        this.burgerId = burgerId;
        this.boissonId = boissonId;
        this.friteId = friteId;
        this.estArchive = estArchive;
    }
    
    public Menu(String nom, String image, int burgerId, int boissonId, int friteId) {
        this();
        this.nom = nom;
        this.image = image;
        this.burgerId = burgerId;
        this.boissonId = boissonId;
        this.friteId = friteId;
    }
    
    
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
    
    public String getImage() {
        return image;
    }
    
    public void setImage(String image) {
        this.image = image;
    }
    
    public int getBurgerId() {
        return burgerId;
    }
    
    public void setBurgerId(int burgerId) {
        this.burgerId = burgerId;
    }
    
    public int getBoissonId() {
        return boissonId;
    }
    
    public void setBoissonId(int boissonId) {
        this.boissonId = boissonId;
    }
    
    public int getFriteId() {
        return friteId;
    }
    
    public void setFriteId(int friteId) {
        this.friteId = friteId;
    }
    
    public boolean isEstArchive() {
        return estArchive;
    }
    
    public void setEstArchive(boolean estArchive) {
        this.estArchive = estArchive;
    }
    
    public Produit getBurger() {
        return burger;
    }
    
    public void setBurger(Produit burger) {
        this.burger = burger;
    }
    
    public Produit getBoisson() {
        return boisson;
    }
    
    public void setBoisson(Produit boisson) {
        this.boisson = boisson;
    }
    
    public Produit getFrite() {
        return frite;
    }
    
    public void setFrite(Produit frite) {
        this.frite = frite;
    }
    
    public double getPrix() {
        double total = 0;
        if (burger != null) total += burger.getPrix();
        if (boisson != null) total += boisson.getPrix();
        if (frite != null) total += frite.getPrix();
        return total;
    }
}
