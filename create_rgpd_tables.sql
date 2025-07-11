-- Tables pour le système RGPD
-- À exécuter dans votre base de données MySQL
-- Version corrigée avec vérifications

-- Supprimer les tables existantes si elles existent (optionnel, décommentez si besoin)
-- DROP TABLE IF EXISTS `rgpd_action_logs`;
-- DROP TABLE IF EXISTS `data_deletion_requests`;
-- DROP TABLE IF EXISTS `user_data_collection`;
-- DROP TABLE IF EXISTS `cookie_consents`;

-- Table pour les consentements des cookies
CREATE TABLE IF NOT EXISTS `cookie_consents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `consent_given` tinyint(1) NOT NULL DEFAULT '0',
  `preferences` text,
  `consent_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_date` (`ip_address`, `consent_date`),
  KEY `idx_consent_date` (`consent_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pour la collecte de données utilisateur
CREATE TABLE IF NOT EXISTS `user_data_collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `collected_data` longtext,
  `collection_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_date` (`ip_address`, `collection_date`),
  KEY `idx_collection_date` (`collection_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pour les demandes de suppression de données
CREATE TABLE IF NOT EXISTS `data_deletion_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255),
  `request_reason` text,
  `status` enum('pending','approved','rejected','processed') NOT NULL DEFAULT 'pending',
  `admin_notes` text,
  `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pour les logs d'actions RGPD
CREATE TABLE IF NOT EXISTS `rgpd_action_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_user_id` int(11),
  `action_type` varchar(50) NOT NULL,
  `target_ip` varchar(45),
  `action_details` text,
  `action_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_user` (`admin_user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_action_date` (`action_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Exemples de données pour tester (optionnel)
INSERT INTO `cookie_consents` (`ip_address`, `user_agent`, `consent_given`, `preferences`, `consent_date`) VALUES
('192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 1, '{"analytics":true,"preferences":true,"marketing":false}', NOW() - INTERVAL 1 DAY),
('192.168.1.101', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X)', 0, '{}', NOW() - INTERVAL 2 DAY),
('192.168.1.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)', 1, '{"analytics":true,"preferences":false,"marketing":true}', NOW() - INTERVAL 3 DAY);

INSERT INTO `user_data_collection` (`ip_address`, `collected_data`, `collection_date`) VALUES
('192.168.1.100', '{"essential":{"ip_address":"192.168.1.100","timestamp":"2025-01-11 10:30:00","page_url":"/shop"},"analytics":{"user_agent":"Mozilla/5.0...","referer":"https://google.com"}}', NOW() - INTERVAL 1 DAY),
('192.168.1.101', '{"essential":{"ip_address":"192.168.1.101","timestamp":"2025-01-11 11:15:00","page_url":"/portfolio"}}', NOW() - INTERVAL 2 DAY),
('192.168.1.102', '{"essential":{"ip_address":"192.168.1.102","timestamp":"2025-01-11 14:45:00","page_url":"/contact"},"preferences":{"theme":"dark","language":"fr"}}', NOW() - INTERVAL 3 DAY);
