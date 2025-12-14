package ism.java.service.impl;

import ism.java.entity.Livreur;
import ism.java.repository.LivreurRepository;
import ism.java.service.LivreurService;

import java.util.List;

public class LivreurServiceImpl implements LivreurService {
    
    private LivreurRepository livreurRepository;
    
    public LivreurServiceImpl(LivreurRepository livreurRepository) {
        this.livreurRepository = livreurRepository;
    }
    
    @Override
    public Livreur addLivreur(String nom, String telephone) {
        if (nom == null || nom.trim().isEmpty()) {
            return null;
        }
        if (telephone == null || telephone.trim().isEmpty()) {
            return null;
        }
        
        Livreur livreur = new Livreur(nom.trim(), telephone.trim());
        return livreurRepository.insert(livreur);
    }
    
    @Override
    public List<Livreur> getAllLivreurs() {
        return livreurRepository.selectAll();
    }
    
    @Override
    public List<Livreur> getAllLivreursIncludingArchived() {
        return livreurRepository.selectAllIncludingArchived();
    }
    
    @Override
    public Livreur getLivreurById(int id) {
        return livreurRepository.selectById(id);
    }
    
    @Override
    public boolean updateLivreur(int id, String nom, String telephone) {
        if (nom == null || nom.trim().isEmpty()) {
            return false;
        }
        if (telephone == null || telephone.trim().isEmpty()) {
            return false;
        }
        
        Livreur livreur = getLivreurById(id);
        if (livreur == null) {
            return false;
        }
        
        livreur.setNom(nom.trim());
        livreur.setTelephone(telephone.trim());
        
        return livreurRepository.update(livreur);
    }
    
    @Override
    public boolean archiveLivreur(int id) {
        Livreur livreur = livreurRepository.selectById(id);
        if (livreur == null) {
            return false;
        }
        return livreurRepository.archive(id);
    }
    
    @Override
    public boolean unarchiveLivreur(int id) {
        Livreur livreur = livreurRepository.selectById(id);
        if (livreur == null) {
            return false;
        }
        return livreurRepository.unarchive(id);
    }
}