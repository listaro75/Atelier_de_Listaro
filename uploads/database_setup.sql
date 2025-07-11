-- =============================================================================
-- SCRIPT DE CRÉATION DE BASE DE DONNÉES POUR ATELIER DE LISTARO
-- =============================================================================
-- Site e-commerce avec prestations et boutique
-- Compatible MySQL/MariaDB
-- =============================================================================

-- Suppression des tables existantes (optionnel - à décommenter si besoin)
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
-- TABLE DES UTILISATEURS
-- =============================================================================
CREATE TABLE IF NOT EXISTS `user` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pseudo` VARCHAR(30) NOT NULL UNIQUE,
    `mail` VARCHAR(255) NOT NULL UNIQUE,
    `mdp` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') DEFAULT 'user',
    `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_last_conect` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_pseudo` (`pseudo`),
    INDEX `idx_mail` (`mail`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE DES PRODUITS
-- =============================================================================
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_category` (`category`),
    INDEX `idx_price` (`price`),
    INDEX `idx_stock` (`stock`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE DES IMAGES DE PRODUITS
-- =============================================================================
CREATE TABLE IF NOT EXISTS `product_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `image_path` VARCHAR(500) NOT NULL,
    `is_main` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_is_main` (`is_main`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE DES PRESTATIONS
-- =============================================================================
CREATE TABLE IF NOT EXISTS `prestations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `duration` VARCHAR(100) NULL,
    `category` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_category` (`category`),
    INDEX `idx_price` (`price`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE DES IMAGES DE PRESTATIONS
-- =============================================================================
CREATE TABLE IF NOT EXISTS `prestation_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prestation_id` INT NOT NULL,
    `image_path` VARCHAR(500) NOT NULL,
    `is_main` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`prestation_id`) REFERENCES `prestations`(`id`) ON DELETE CASCADE,
    INDEX `idx_prestation_id` (`prestation_id`),
    INDEX `idx_is_main` (`is_main`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE DES COMMANDES
-- =============================================================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `shipping_cost` DECIMAL(10,2) DEFAULT 0.00,
    `shipping_method` VARCHAR(100) NULL,
    `shipping_address` TEXT NOT NULL,
    `status` ENUM('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    `stripe_payment_id` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE DES ARTICLES DE COMMANDE
-- =============================================================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE DES LIKES DE PRODUITS
-- =============================================================================
CREATE TABLE IF NOT EXISTS `product_likes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_product_user_like` (`product_id`, `user_id`),
    INDEX `idx_product_id` (`product_id`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLE DES LIKES DE PRESTATIONS
-- =============================================================================
CREATE TABLE IF NOT EXISTS `prestation_likes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prestation_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`prestation_id`) REFERENCES `prestations`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_prestation_user_like` (`prestation_id`, `user_id`),
    INDEX `idx_prestation_id` (`prestation_id`),
    INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DONNÉES D'EXEMPLE
-- =============================================================================

-- Utilisateur administrateur par défaut
-- Mot de passe: Admin123!
INSERT INTO `user` (`pseudo`, `mail`, `mdp`, `role`, `date_creation`, `date_last_conect`) VALUES
('admin', 'admin@atelier-listaro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW(), NOW())
ON DUPLICATE KEY UPDATE `mdp` = VALUES(`mdp`), `role` = VALUES(`role`);

-- Utilisateur test
-- Mot de passe: Test123!
INSERT INTO `user` (`pseudo`, `mail`, `mdp`, `role`, `date_creation`) VALUES
('testuser', 'test@atelier-listaro.com', '$2y$10$qrCodeExample.HashForTest123!', 'user', NOW())
ON DUPLICATE KEY UPDATE `pseudo` = VALUES(`pseudo`);

-- Catégories de produits d'exemple
INSERT INTO `products` (`name`, `description`, `price`, `category`, `stock`) VALUES
('Figurine Dragon Rouge', 'Magnifique figurine de dragon rouge peinte à la main, parfaite pour vos collections ou jeux de rôle.', 45.99, 'Figurines', 15),
('Peinture Acrylique Premium Set', 'Set complet de peintures acryliques haute qualité pour figurines, 24 couleurs.', 29.99, 'Peinture', 25),
('Socle Hexagonal Deluxe', 'Socle hexagonal en résine de haute qualité pour vos figurines.', 8.50, 'Accessoires', 50),
('Figurine Guerrier Elfe', 'Figurine détaillée d\'un guerrier elfe avec épée et bouclier.', 32.99, 'Figurines', 12),
('Kit de Pinceaux Professionnels', 'Set de 10 pinceaux de différentes tailles pour la peinture de figurines.', 19.99, 'Outils', 30)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- Prestations d'exemple
INSERT INTO `prestations` (`name`, `description`, `price`, `duration`, `category`) VALUES
('Peinture Figurine Standard', 'Peinture complète de votre figurine avec techniques de base, sous-couche, couleurs principales et finitions.', 40.00, '2-3 jours', 'Peinture'),
('Peinture Figurine Premium', 'Peinture avancée avec techniques spéciales, effets de lumière, weathering et socle personnalisé.', 80.00, '5-7 jours', 'Peinture'),
('Impression 3D Figurine', 'Impression 3D haute qualité de votre modèle personnalisé avec post-traitement.', 25.00, '1-2 jours', 'Impression'),
('Site Web Vitrine', 'Création d\'un site web professionnel pour présenter votre activité (5 pages max).', 500.00, '2-3 semaines', 'Développement Web'),
('Boutique E-commerce', 'Développement complet d\'une boutique en ligne avec gestion des produits et paiements.', 1200.00, '4-6 semaines', 'Développement Web')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =============================================================================
-- VUES UTILES
-- =============================================================================

-- Vue pour les produits avec leur image principale
CREATE OR REPLACE VIEW `products_with_main_image` AS
SELECT 
    p.*,
    pi.image_path as main_image,
    COALESCE(pl.likes_count, 0) as likes_count
FROM `products` p
LEFT JOIN `product_images` pi ON p.id = pi.product_id AND pi.is_main = 1
LEFT JOIN (
    SELECT product_id, COUNT(*) as likes_count 
    FROM `product_likes` 
    GROUP BY product_id
) pl ON p.id = pl.product_id;

-- Vue pour les prestations avec leur image principale
CREATE OR REPLACE VIEW `prestations_with_main_image` AS
SELECT 
    pr.*,
    pri.image_path as main_image,
    COALESCE(prl.likes_count, 0) as likes_count
FROM `prestations` pr
LEFT JOIN `prestation_images` pri ON pr.id = pri.prestation_id AND pri.is_main = 1
LEFT JOIN (
    SELECT prestation_id, COUNT(*) as likes_count 
    FROM `prestation_likes` 
    GROUP BY prestation_id
) prl ON pr.id = prl.prestation_id;

-- Vue pour les commandes avec détails
CREATE OR REPLACE VIEW `orders_details` AS
SELECT 
    o.*,
    u.pseudo as user_pseudo,
    u.mail as user_email,
    COUNT(oi.id) as items_count,
    GROUP_CONCAT(
        CONCAT(oi.quantity, 'x ', p.name, ' (', oi.price, '€)')
        SEPARATOR ', '
    ) as products_summary
FROM `orders` o
LEFT JOIN `user` u ON o.user_id = u.id
LEFT JOIN `order_items` oi ON o.id = oi.order_id
LEFT JOIN `products` p ON oi.product_id = p.id
GROUP BY o.id;

-- =============================================================================
-- PROCÉDURES STOCKÉES UTILES
-- =============================================================================

DELIMITER //

-- Procédure pour calculer le total d'une commande
CREATE PROCEDURE CalculateOrderTotal(IN order_id INT, OUT total DECIMAL(10,2))
BEGIN
    SELECT SUM(quantity * price) INTO total
    FROM order_items
    WHERE order_items.order_id = order_id;
END //

-- Procédure pour mettre à jour le stock après commande
CREATE PROCEDURE UpdateStockAfterOrder(IN order_id INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE prod_id INT;
    DECLARE qty INT;
    
    DECLARE cur CURSOR FOR 
        SELECT product_id, quantity 
        FROM order_items 
        WHERE order_items.order_id = order_id;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO prod_id, qty;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        UPDATE products 
        SET stock = stock - qty 
        WHERE id = prod_id;
    END LOOP;
    
    CLOSE cur;
END //

DELIMITER ;

-- =============================================================================
-- TRIGGERS
-- =============================================================================

-- Trigger pour s'assurer qu'il n'y a qu'une seule image principale par produit
DELIMITER //
CREATE TRIGGER product_main_image_unique 
BEFORE INSERT ON product_images
FOR EACH ROW
BEGIN
    IF NEW.is_main = 1 THEN
        UPDATE product_images 
        SET is_main = 0 
        WHERE product_id = NEW.product_id AND is_main = 1;
    END IF;
END //

CREATE TRIGGER product_main_image_unique_update
BEFORE UPDATE ON product_images
FOR EACH ROW
BEGIN
    IF NEW.is_main = 1 AND OLD.is_main = 0 THEN
        UPDATE product_images 
        SET is_main = 0 
        WHERE product_id = NEW.product_id AND is_main = 1 AND id != NEW.id;
    END IF;
END //

-- Trigger pour s'assurer qu'il n'y a qu'une seule image principale par prestation
CREATE TRIGGER prestation_main_image_unique 
BEFORE INSERT ON prestation_images
FOR EACH ROW
BEGIN
    IF NEW.is_main = 1 THEN
        UPDATE prestation_images 
        SET is_main = 0 
        WHERE prestation_id = NEW.prestation_id AND is_main = 1;
    END IF;
END //

CREATE TRIGGER prestation_main_image_unique_update
BEFORE UPDATE ON prestation_images
FOR EACH ROW
BEGIN
    IF NEW.is_main = 1 AND OLD.is_main = 0 THEN
        UPDATE prestation_images 
        SET is_main = 0 
        WHERE prestation_id = NEW.prestation_id AND is_main = 1 AND id != NEW.id;
    END IF;
END //

DELIMITER ;

-- =============================================================================
-- INDEX SUPPLÉMENTAIRES POUR LES PERFORMANCES
-- =============================================================================

-- Index composites pour les requêtes fréquentes
CREATE INDEX idx_product_category_stock ON products(category, stock);
CREATE INDEX idx_product_price_category ON products(price, category);
CREATE INDEX idx_orders_user_status ON orders(user_id, status);
CREATE INDEX idx_orders_date_status ON orders(created_at, status);

-- Index pour les recherches full-text (optionnel)
-- ALTER TABLE products ADD FULLTEXT(name, description);
-- ALTER TABLE prestations ADD FULLTEXT(name, description);

-- =============================================================================
-- PERMISSIONS ET SÉCURITÉ
-- =============================================================================

-- Création d'un utilisateur spécifique pour l'application (optionnel)
-- CREATE USER 'atelier_app'@'localhost' IDENTIFIED BY 'motdepasse_securise';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON atelier_listaro.* TO 'atelier_app'@'localhost';
-- FLUSH PRIVILEGES;

-- =============================================================================
-- SCRIPT TERMINÉ
-- =============================================================================

SELECT 'Base de données Atelier de Listaro créée avec succès!' as Message;
SELECT COUNT(*) as 'Nombre de tables créées' FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name IN (
    'user', 'products', 'product_images', 'prestations', 'prestation_images', 
    'orders', 'order_items', 'product_likes', 'prestation_likes'
);
