package ism.java.service.impl;

import ism.java.entity.Menu;
import ism.java.entity.Produit;
import ism.java.repository.MenuRepository;
import ism.java.repository.ProduitRepository;
import ism.java.service.MenuService;

import java.util.List;

public class MenuServiceImpl implements MenuService {
    
    private MenuRepository menuRepository;
    private ProduitRepository produitRepository;
    
    public MenuServiceImpl(MenuRepository menuRepository, ProduitRepository produitRepository) {
        this.menuRepository = menuRepository;
        this.produitRepository = produitRepository;
    }
    
    @Override
    public Menu addMenu(String nom, String image, int burgerId, int boissonId, int friteId) {
        if (nom == null || nom.trim().isEmpty()) {
            return null;
        }
        
        if (!validateMenuProducts(burgerId, boissonId, friteId)) {
            return null;
        }
        
        Menu menu = new Menu(nom.trim(), image, burgerId, boissonId, friteId);
        return menuRepository.insert(menu);
    }
    
    @Override
    public List<Menu> getAllMenus() {
        return menuRepository.selectAll();
    }
    
    @Override
    public List<Menu> getAllMenusIncludingArchived() {
        return menuRepository.selectAllIncludingArchived();
    }
    
    @Override
    public Menu getMenuById(int id) {
        return menuRepository.selectById(id);
    }
    
    @Override
    public boolean updateMenu(int id, String nom, String image, int burgerId, int boissonId, int friteId) {
        if (nom == null || nom.trim().isEmpty()) {
            return false;
        }
        
        Menu menu = getMenuById(id);
        if (menu == null) {
            return false;
        }
        
        if (!validateMenuProducts(burgerId, boissonId, friteId)) {
            return false;
        }
        
        menu.setNom(nom.trim());
        menu.setImage(image);
        menu.setBurgerId(burgerId);
        menu.setBoissonId(boissonId);
        menu.setFriteId(friteId);
        
        return menuRepository.update(menu);
    }
    
    @Override
    public boolean archiveMenu(int id) {
        Menu menu = getMenuById(id);
        if (menu == null) {
            return false;
        }
        return menuRepository.archive(id);
    }
    
    @Override
    public boolean unarchiveMenu(int id) {
        Menu menu = menuRepository.selectById(id);
        if (menu == null) {
            return false;
        }
        return menuRepository.unarchive(id);
    }
    
    private boolean validateMenuProducts(int burgerId, int boissonId, int friteId) {
        Produit burger = produitRepository.selectById(burgerId);
        if (burger == null || !burger.isBurger() || burger.isEstArchive()) {
            return false;
        }
        
        Produit boisson = produitRepository.selectById(boissonId);
        if (boisson == null || !boisson.isBoisson() || boisson.isEstArchive()) {
            return false;
        }
        
        Produit frite = produitRepository.selectById(friteId);
        if (frite == null || !frite.isFrite() || frite.isEstArchive()) {
            return false;
        }
        
        return true;
    }
}
