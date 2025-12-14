package ism.java.config;

import com.cloudinary.Cloudinary;
import com.cloudinary.utils.ObjectUtils;

import java.io.File;
import java.util.Map;

public class CloudinaryConfig {
    
    private static final String CLOUD_NAME = "dooczrzjq";
    private static final String API_KEY = "796581289217683";
    private static final String API_SECRET = "HuosLQKtRIHMpo-yETqFnE3rja4";
    
    private static Cloudinary cloudinary = null;
    
    private CloudinaryConfig() {}
    
    public static Cloudinary getCloudinary() {
        if (cloudinary == null) {
            cloudinary = new Cloudinary(ObjectUtils.asMap(
                "cloud_name", CLOUD_NAME,
                "api_key", API_KEY,
                "api_secret", API_SECRET
            ));
        }
        return cloudinary;
    }
    
    public static String uploadImage(String filePath, String folder) {
        try {
            File file = new File(filePath);
            if (!file.exists()) {
                System.out.println("Fichier non trouve : " + filePath);
                return null;
            }
            
            Map<String, Object> result = getCloudinary().uploader().upload(file, ObjectUtils.asMap(
                "folder", "brasil-burger/" + folder
            ));
            
            return (String) result.get("secure_url");
        } catch (Exception e) {
            System.out.println("Erreur upload : " + e.getMessage());
            return null;
        }
    }
    
    public static String uploadBurgerImage(String filePath) {
        return uploadImage(filePath, "burgers");
    }
    
    public static String uploadComplementImage(String filePath) {
        return uploadImage(filePath, "complements");
    }
    
    public static String uploadMenuImage(String filePath) {
        return uploadImage(filePath, "menus");
    }
}