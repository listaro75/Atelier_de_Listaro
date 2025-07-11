-- =============================================================================
-- BASE DE DONNÉES ATELIER DE LISTARO
-- =============================================================================
-- Script SQL pour hébergement partagé
-- Importez ce fichier directement dans phpMyAdmin dans votre base existante
-- =============================================================================

-- Suppression des tables existantes si elles existent (optionnel)
-- Décommentez les lignes suivantes si vous voulez réinitialiser complètement
-- DROP TABLE IF EXISTS order_items;
-- DROP TABLE IF EXISTS orders;
-- DROP TABLE IF EXISTS product_likes;
-- DROP TABLE IF EXISTS prestation_likes;
-- DROP TABLE IF EXISTS product_images;
-- DROP TABLE IF EXISTS prestation_images;
-- DROP TABLE IF EXISTS products;
-- DROP TABLE IF EXISTS prestations;
-- DROP TABLE IF EXISTS user;

-- =============================================================================
-- TABLES
-- =============================================================================

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pseudo` varchar(30) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `mdp` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_last_conect` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pseudo` (`pseudo`),
  UNIQUE KEY `mail` (`mail`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des produits
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(100) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_price` (`price`),
  KEY `idx_stock` (`stock`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des images de produits
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `is_main` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `idx_is_main` (`is_main`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des prestations
CREATE TABLE IF NOT EXISTS `prestations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_price` (`price`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des images de prestations
CREATE TABLE IF NOT EXISTS `prestation_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestation_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `is_main` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `prestation_id` (`prestation_id`),
  KEY `idx_is_main` (`is_main`),
  CONSTRAINT `prestation_images_ibfk_1` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des commandes
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_cost` decimal(10,2) DEFAULT '0.00',
  `shipping_method` varchar(100) DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `status` enum('pending','paid','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `stripe_payment_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des articles de commande
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des likes de produits
CREATE TABLE IF NOT EXISTS `product_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_user_like` (`product_id`,`user_id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `product_likes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des likes de prestations
CREATE TABLE IF NOT EXISTS `prestation_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prestation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_prestation_user_like` (`prestation_id`,`user_id`),
  KEY `prestation_id` (`prestation_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `prestation_likes_ibfk_1` FOREIGN KEY (`prestation_id`) REFERENCES `prestations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `prestation_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DONNÉES D'EXEMPLE
-- =============================================================================

-- Utilisateur administrateur (mot de passe: Admin123!)
INSERT IGNORE INTO `user` (`pseudo`, `mail`, `mdp`, `role`, `date_creation`, `date_last_conect`) VALUES
('admin', 'admin@atelier-listaro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW(), NOW());

-- Utilisateur test (mot de passe: Test123!)
INSERT IGNORE INTO `user` (`pseudo`, `mail`, `mdp`, `role`, `date_creation`) VALUES
('testuser', 'test@atelier-listaro.com', '$2y$10$Q4f8T.8gF9h8qFo2rG5qDOJKd1vhN3.aBcD3.EfG7hI9jKl2MnO3p', 'user', NOW());

-- Produits d'exemple
INSERT IGNORE INTO `products` (`name`, `description`, `price`, `category`, `stock`) VALUES
('Figurine Dragon Rouge', 'Magnifique figurine de dragon rouge peinte à la main, parfaite pour vos collections ou jeux de rôle.', 45.99, 'Figurines', 15),
('Peinture Acrylique Premium Set', 'Set complet de peintures acryliques haute qualité pour figurines, 24 couleurs.', 29.99, 'Peinture', 25),
('Socle Hexagonal Deluxe', 'Socle hexagonal en résine de haute qualité pour vos figurines.', 8.50, 'Accessoires', 50),
('Figurine Guerrier Elfe', 'Figurine détaillée d\'un guerrier elfe avec épée et bouclier.', 32.99, 'Figurines', 12),
('Kit de Pinceaux Professionnels', 'Set de 10 pinceaux de différentes tailles pour la peinture de figurines.', 19.99, 'Outils', 30);

-- Prestations d'exemple
INSERT IGNORE INTO `prestations` (`name`, `description`, `price`, `duration`, `category`) VALUES
('Peinture Figurine Standard', 'Peinture complète de votre figurine avec techniques de base, sous-couche, couleurs principales et finitions.', 40.00, '2-3 jours', 'Peinture'),
('Peinture Figurine Premium', 'Peinture avancée avec techniques spéciales, effets de lumière, weathering et socle personnalisé.', 80.00, '5-7 jours', 'Peinture'),
('Impression 3D Figurine', 'Impression 3D haute qualité de votre modèle personnalisé avec post-traitement.', 25.00, '1-2 jours', 'Impression'),
('Site Web Vitrine', 'Création d\'un site web professionnel pour présenter votre activité (5 pages max).', 500.00, '2-3 semaines', 'Développement Web'),
('Boutique E-commerce', 'Développement complet d\'une boutique en ligne avec gestion des produits et paiements.', 1200.00, '4-6 semaines', 'Développement Web');
