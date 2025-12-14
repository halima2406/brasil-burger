package ism.java.config;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;


public class DatabaseConfig {
    
    private static final String HOST = "ep-divine-lab-adyo1b7f-pooler.c-2.us-east-1.aws.neon.tech";
    private static final String DATABASE = "brasil_burger";
    private static final String USER = "neondb_owner";
    private static final String PASSWORD = "npg_7DL6FeUdkJYl";
    private static final String URL = "jdbc:postgresql://" + HOST + "/" + DATABASE + "?sslmode=require";
    
    private static Connection connection = null;
    
    private DatabaseConfig() {}
    
    public static Connection getConnection() throws SQLException {
        if (connection == null || connection.isClosed()) {
            try {
                Class.forName("org.postgresql.Driver");
                connection = DriverManager.getConnection(URL, USER, PASSWORD);
            } catch (ClassNotFoundException e) {
                System.err.println("Driver PostgreSQL non trouve");
                return null;
            }
        }
        return connection;
    }
    
    public static void closeConnection() {
        if (connection != null) {
            try {
                connection.close();
                connection = null;
            } catch (SQLException e) {
                System.err.println("Erreur fermeture connexion");
            }
        }
    }
    
    public static boolean testConnection() {
        try {
            Connection conn = getConnection();
            return conn != null && !conn.isClosed();
        } catch (SQLException e) {
            return false;
        }
    }
}
