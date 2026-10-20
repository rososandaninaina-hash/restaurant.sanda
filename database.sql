-- Base de données pédagogique : gestion de restaurant
-- Importer ce fichier dans phpMyAdmin. Base de données : restaurant_db.
CREATE DATABASE IF NOT EXISTS restaurant_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurant_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS restaurant_tables;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','client') NOT NULL DEFAULT 'client',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(500) NOT NULL DEFAULT '',
    price DECIMAL(12,2) NOT NULL,
    emoji VARCHAR(16) NOT NULL DEFAULT '🍽️',
    available TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT chk_menu_price CHECK (price > 0)
) ENGINE=InnoDB;

CREATE TABLE restaurant_tables (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_number SMALLINT UNSIGNED NOT NULL UNIQUE,
    seats TINYINT UNSIGNED NOT NULL DEFAULT 4,
    status ENUM('libre','occupee','indisponible') NOT NULL DEFAULT 'libre'
) ENGINE=InnoDB;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    table_id INT UNSIGNED NULL,
    order_type ENUM('en_ligne','sur_place') NOT NULL DEFAULT 'en_ligne',
    status ENUM('nouvelle','en_preparation','prete','terminee','annulee') NOT NULL DEFAULT 'nouvelle',
    payment_status ENUM('non_paye','paye') NOT NULL DEFAULT 'non_paye',
    total_amount DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME NULL,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_orders_table FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE SET NULL,
    INDEX idx_orders_created (created_at),
    INDEX idx_orders_status (status),
    INDEX idx_orders_payment (payment_status)
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    menu_item_id INT UNSIGNED NULL,
    item_name VARCHAR(120) NOT NULL, -- Copie du nom pour garder l'historique.
    quantity TINYINT UNSIGNED NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL, -- Prix au moment de la commande.
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL,
    CONSTRAINT chk_order_quantity CHECK (quantity > 0)
) ENGINE=InnoDB;

-- Comptes de démonstration :
-- Administrateur : admin@restaurant.local / Admin123!
-- Client         : client@restaurant.local / Client123!
-- Ces hachages sont créés avec PHP password_hash() et PASSWORD_DEFAULT.
INSERT INTO users (name,email,password_hash,role) VALUES
('Administrateur','admin@restaurant.local','$2y$12$FuZmIyjzRR.DeLkr7aWQJuZABJWPnPgbE.Op4pZxsOpVzKjyscVAC','admin'),
('Client Démo','client@restaurant.local','$2y$12$mvltol8.qmoSu7r7EvStFOqQK9rk4aOEtmoOusaPLX/OORixQ6ocy','client');

INSERT INTO categories (name) VALUES
('Entrées'),('Plats principaux'),('Desserts'),('Boissons');

INSERT INTO menu_items (category_id,name,description,price,emoji,available) VALUES
(1,'Salade fraîche','Salade de saison, tomate et vinaigrette.',8000,'🥗',1),
(1,'Sambos (3 pièces)','Petits sambos croustillants.',5000,'🥟',1),
(2,'Romazava','Plat malgache traditionnel avec brèdes et viande.',18000,'🍲',1),
(2,'Poulet grillé','Poulet grillé accompagné de riz.',22000,'🍗',1),
(2,'Poisson du jour','Poisson selon arrivage avec accompagnement.',25000,'🐟',1),
(3,'Salade de fruits','Fruits frais de saison.',7000,'🍉',1),
(3,'Crêpe au chocolat','Crêpe servie avec chocolat.',9000,'🥞',1),
(4,'Eau minérale','Bouteille 50 cl.',3000,'💧',1),
(4,'Jus naturel','Jus de fruits de saison.',6000,'🧃',1);

INSERT INTO restaurant_tables (table_number,seats,status) VALUES
(1,2,'libre'),(2,2,'libre'),(3,4,'libre'),(4,4,'libre'),(5,6,'libre');
