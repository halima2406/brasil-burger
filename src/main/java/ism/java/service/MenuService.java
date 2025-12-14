package ism.java.service;

import ism.java.entity.Menu;

import java.util.List;

public interface MenuService {
    
    Menu addMenu(String nom, String image, int burgerId, int boissonId, int friteId);
    
    List<Menu> getAllMenus();
    
    List<Menu> getAllMenusIncludingArchived();
    
    Menu getMenuById(int id);
    
    boolean updateMenu(int id, String nom, String image, int burgerId, int boissonId, int friteId);
    
    boolean archiveMenu(int id);
    
    boolean unarchiveMenu(int id);
}
