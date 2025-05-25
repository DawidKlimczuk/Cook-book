-- Kompletny plik SQL dla aplikacji Cook Book
-- Zawiera wszystkio

-- Tworzenie bazy danych
CREATE DATABASE IF NOT EXISTS recipe_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE recipe_app;

-- Usuwanie istniejących tabel (jeśli istnieją)
DROP TABLE IF EXISTS recipe_tags;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS pantry;
DROP TABLE IF EXISTS recipes;
DROP TABLE IF EXISTS tags;
DROP TABLE IF EXISTS users;
DROP VIEW IF EXISTS recipe_stats;

-- Tabela użytkowników
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    newsletter BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela przepisów
CREATE TABLE recipes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id VARCHAR(50) DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    ingredients TEXT NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category_id)
);

-- Tabela tagów
CREATE TABLE tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL
);

-- Tabela łącząca przepisy z tagami
CREATE TABLE recipe_tags (
    recipe_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (recipe_id, tag_id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Tabela ocen
CREATE TABLE ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    recipe_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_recipe_rating (user_id, recipe_id),
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabela ulubionych
CREATE TABLE favorites (
    user_id INT NOT NULL,
    recipe_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, recipe_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE
);

-- Tabela spiżarni
CREATE TABLE pantry (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_product (user_id, product_name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Widok dla statystyk przepisów
CREATE VIEW recipe_stats AS
SELECT 
    r.id,
    r.title,
    r.user_id,
    u.username as author,
    r.image_path,
    r.created_at,
    r.category_id,
    COALESCE(AVG(rt.rating), 0) as avg_rating,
    COUNT(DISTINCT rt.user_id) as rating_count
FROM recipes r
LEFT JOIN users u ON r.user_id = u.id
LEFT JOIN ratings rt ON r.id = rt.recipe_id
GROUP BY r.id;

-- Wstawianie tagów
INSERT INTO tags (name) VALUES 
('fit'),
('vegan'),
('vegetarian'),
('low_cal'),
('keto'),
('bez_glutenu'),
('bez_laktozy'),
('wysokobialkowe'),
('niskokaloryczne'),
('superfood'),
('szybkie'),
('na_slodko'),
('latwy_przepis');


