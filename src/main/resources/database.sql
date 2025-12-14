-- Script SQL pour PostgreSQL (Neon)

DROP TABLE IF EXISTS paiements CASCADE;
DROP TABLE IF EXISTS ligne_commandes CASCADE;
DROP TABLE IF EXISTS commandes CASCADE;
DROP TABLE IF EXISTS menus CASCADE;
DROP TABLE IF EXISTS produits CASCADE;
DROP TABLE IF EXISTS livreurs CASCADE;
DROP TABLE IF EXISTS zones CASCADE;
DROP TABLE IF EXISTS users CASCADE;

DROP TYPE IF EXISTS type_produit CASCADE;
DROP TYPE IF EXISTS type_complement CASCADE;
DROP TYPE IF EXISTS statut_commande CASCADE;
DROP TYPE IF EXISTS type_consommation CASCADE;
DROP TYPE IF EXISTS type_paiement CASCADE;
DROP TYPE IF EXISTS type_user CASCADE;

CREATE TYPE type_produit AS ENUM ('BURGER', 'COMPLEMENT');
CREATE TYPE type_complement AS ENUM ('BOISSON', 'FRITE');
CREATE TYPE statut_commande AS ENUM ('EN_COURS', 'VALIDEE', 'TERMINEE', 'ANNULEE');
CREATE TYPE type_consommation AS ENUM ('SUR_PLACE', 'A_EMPORTER', 'LIVRAISON');
CREATE TYPE type_paiement AS ENUM ('WAVE', 'OM');
CREATE TYPE type_user AS ENUM ('CLIENT', 'GESTIONNAIRE');

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    type_user type_user NOT NULL,
    telephone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE zones (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    quartier VARCHAR(255) NOT NULL,
    prix DECIMAL(10,2) NOT NULL
);

CREATE TABLE livreurs (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    zone_id INT REFERENCES zones(id)
);

CREATE TABLE produits (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    image VARCHAR(500),
    type_produit type_produit NOT NULL,
    type_complement type_complement,
    est_archive BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE menus (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    image VARCHAR(500),
    burger_id INT NOT NULL REFERENCES produits(id),
    boisson_id INT NOT NULL REFERENCES produits(id),
    frite_id INT NOT NULL REFERENCES produits(id),
    est_archive BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE commandes (
    id SERIAL PRIMARY KEY,
    client_id INT NOT NULL REFERENCES users(id),
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut statut_commande DEFAULT 'EN_COURS',
    type_consommation type_consommation NOT NULL,
    montant_total DECIMAL(10,2) NOT NULL,
    livreur_id INT REFERENCES livreurs(id),
    zone_id INT REFERENCES zones(id)
);

CREATE TABLE ligne_commandes (
    id SERIAL PRIMARY KEY,
    commande_id INT NOT NULL REFERENCES commandes(id),
    produit_id INT REFERENCES produits(id),
    menu_id INT REFERENCES menus(id),
    quantite INT NOT NULL DEFAULT 1,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    montant_total DECIMAL(10,2) NOT NULL,
    type_ligne VARCHAR(20) NOT NULL
);

CREATE TABLE paiements (
    id SERIAL PRIMARY KEY,
    commande_id INT UNIQUE NOT NULL REFERENCES commandes(id),
    montant DECIMAL(10,2) NOT NULL,
    date_paiement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    type_paiement type_paiement NOT NULL
);

-- Donnees de test

INSERT INTO zones (nom, quartier, prix) VALUES 
('Zone 1', 'Plateau, Medina', 500),
('Zone 2', 'Fann, Point E, Mermoz', 750),
('Zone 3', 'Parcelles, Guediawaye', 1000);

INSERT INTO livreurs (nom, numero, zone_id) VALUES 
('Moussa Diop', '771234567', 1),
('Amadou Fall', '781234567', 2),
('Ibrahima Ndiaye', '761234567', 3);

INSERT INTO users (nom, prenom, email, mot_de_passe, type_user) VALUES 
('Admin', 'Brasil', 'admin@brasilburger.com', 'admin123', 'GESTIONNAIRE');

INSERT INTO produits (nom, prix, image, type_produit) VALUES 
('Classic Burger', 2500, NULL, 'BURGER'),
('Cheese Burger', 3000, NULL, 'BURGER'),
('Double Burger', 4000, NULL, 'BURGER'),
('Big Brasil', 4500, NULL, 'BURGER');

INSERT INTO produits (nom, prix, image, type_produit, type_complement) VALUES 
('Coca Cola', 500, NULL, 'COMPLEMENT', 'BOISSON'),
('Fanta', 500, NULL, 'COMPLEMENT', 'BOISSON'),
('Sprite', 500, NULL, 'COMPLEMENT', 'BOISSON'),
('Eau Minerale', 300, NULL, 'COMPLEMENT', 'BOISSON');

INSERT INTO produits (nom, prix, image, type_produit, type_complement) VALUES 
('Frites Simples', 800, NULL, 'COMPLEMENT', 'FRITE'),
('Frites Cheese', 1000, NULL, 'COMPLEMENT', 'FRITE'),
('Frites Bacon', 1200, NULL, 'COMPLEMENT', 'FRITE');

INSERT INTO menus (nom, image, burger_id, boisson_id, frite_id) VALUES 
('Menu Classic', NULL, 1, 5, 9),
('Menu Cheese', NULL, 2, 6, 9),
('Menu Double', NULL, 3, 5, 10),
('Menu Big Brasil', NULL, 4, 7, 11);
