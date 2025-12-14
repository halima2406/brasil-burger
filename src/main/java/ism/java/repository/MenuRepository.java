package ism.java.repository;

import ism.java.entity.Menu;

import java.util.List;

public interface MenuRepository {
    
    Menu insert(Menu menu);
    
    Menu selectById(int id);
    
    List<Menu> selectAll();
    
    List<Menu> selectAllIncludingArchived();
    
    boolean update(Menu menu);
    
    boolean archive(int id);
    
    boolean unarchive(int id);
    
    boolean delete(int id);
}
