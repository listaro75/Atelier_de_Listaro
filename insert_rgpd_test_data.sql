-- Données de test pour les tables RGPD
-- À exécuter APRÈS avoir créé les tables avec create_rgpd_tables.sql

-- Vérifier que les tables existent avant d'insérer
-- Si vous obtenez une erreur, exécutez d'abord create_rgpd_tables.sql

-- Insérer des exemples de consentements (seulement si la table est vide)
INSERT INTO `cookie_consents` (`ip_address`, `user_agent`, `consent_given`, `preferences`, `consent_date`) 
SELECT * FROM (SELECT 
    '192.168.1.100' as ip_address,
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' as user_agent,
    1 as consent_given,
    '{"analytics":true,"preferences":true,"marketing":false}' as preferences,
    DATE_SUB(NOW(), INTERVAL 1 DAY) as consent_date
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `cookie_consents` WHERE `ip_address` = '192.168.1.100'
);

INSERT INTO `cookie_consents` (`ip_address`, `user_agent`, `consent_given`, `preferences`, `consent_date`) 
SELECT * FROM (SELECT 
    '192.168.1.101' as ip_address,
    'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X)' as user_agent,
    0 as consent_given,
    '{}' as preferences,
    DATE_SUB(NOW(), INTERVAL 2 DAY) as consent_date
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `cookie_consents` WHERE `ip_address` = '192.168.1.101'
);

INSERT INTO `cookie_consents` (`ip_address`, `user_agent`, `consent_given`, `preferences`, `consent_date`) 
SELECT * FROM (SELECT 
    '192.168.1.102' as ip_address,
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)' as user_agent,
    1 as consent_given,
    '{"analytics":true,"preferences":false,"marketing":true}' as preferences,
    DATE_SUB(NOW(), INTERVAL 3 DAY) as consent_date
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `cookie_consents` WHERE `ip_address` = '192.168.1.102'
);

-- Ajouter quelques consentements supplémentaires pour les statistiques
INSERT INTO `cookie_consents` (`ip_address`, `user_agent`, `consent_given`, `preferences`, `consent_date`) 
SELECT * FROM (SELECT 
    '192.168.1.103' as ip_address,
    'Mozilla/5.0 (Android 11; Mobile)' as user_agent,
    1 as consent_given,
    '{"analytics":true,"preferences":true,"marketing":true}' as preferences,
    DATE_SUB(NOW(), INTERVAL 4 DAY) as consent_date
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `cookie_consents` WHERE `ip_address` = '192.168.1.103'
);

INSERT INTO `cookie_consents` (`ip_address`, `user_agent`, `consent_given`, `preferences`, `consent_date`) 
SELECT * FROM (SELECT 
    '192.168.1.104' as ip_address,
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0)' as user_agent,
    0 as consent_given,
    '{}' as preferences,
    DATE_SUB(NOW(), INTERVAL 5 DAY) as consent_date
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `cookie_consents` WHERE `ip_address` = '192.168.1.104'
);

-- Insérer des exemples de collecte de données
INSERT INTO `user_data_collection` (`ip_address`, `collected_data`, `collection_date`) 
SELECT * FROM (SELECT 
    '192.168.1.100' as ip_address,
    '{"essential":{"ip_address":"192.168.1.100","timestamp":"2025-07-11 10:30:00","page_url":"/shop"},"analytics":{"user_agent":"Mozilla/5.0 (Windows NT 10.0; Win64; x64)","referer":"https://google.com","screen_resolution":"1920x1080"},"preferences":{"theme":"light","language":"fr"}}' as collected_data,
    DATE_SUB(NOW(), INTERVAL 1 DAY) as collection_date
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `user_data_collection` WHERE `ip_address` = '192.168.1.100' AND DATE(collection_date) = DATE(DATE_SUB(NOW(), INTERVAL 1 DAY))
);

INSERT INTO `user_data_collection` (`ip_address`, `collected_data`, `collection_date`) 
SELECT * FROM (SELECT 
    '192.168.1.101' as ip_address,
    '{"essential":{"ip_address":"192.168.1.101","timestamp":"2025-07-11 11:15:00","page_url":"/portfolio"}}' as collected_data,
    DATE_SUB(NOW(), INTERVAL 2 DAY) as collection_date
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `user_data_collection` WHERE `ip_address` = '192.168.1.101' AND DATE(collection_date) = DATE(DATE_SUB(NOW(), INTERVAL 2 DAY))
);

INSERT INTO `user_data_collection` (`ip_address`, `collected_data`, `collection_date`) 
SELECT * FROM (SELECT 
    '192.168.1.102' as ip_address,
    '{"essential":{"ip_address":"192.168.1.102","timestamp":"2025-07-11 14:45:00","page_url":"/contact"},"preferences":{"theme":"dark","language":"fr"},"marketing":{"utm_source":"facebook","utm_campaign":"summer2025"}}' as collected_data,
    DATE_SUB(NOW(), INTERVAL 3 DAY) as collection_date
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `user_data_collection` WHERE `ip_address` = '192.168.1.102' AND DATE(collection_date) = DATE(DATE_SUB(NOW(), INTERVAL 3 DAY))
);

INSERT INTO `user_data_collection` (`ip_address`, `collected_data`, `collection_date`) 
SELECT * FROM (SELECT 
    '192.168.1.103' as ip_address,
    '{"essential":{"ip_address":"192.168.1.103","timestamp":"2025-07-11 16:20:00","page_url":"/prestations"},"analytics":{"user_agent":"Mozilla/5.0 (Android 11; Mobile)","referer":"direct","browser_language":"fr-FR"}}' as collected_data,
    DATE_SUB(NOW(), INTERVAL 4 DAY) as collection_date
) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM `user_data_collection` WHERE `ip_address` = '192.168.1.103' AND DATE(collection_date) = DATE(DATE_SUB(NOW(), INTERVAL 4 DAY))
);

-- Ajouter quelques collectes récentes pour les tests
INSERT INTO `user_data_collection` (`ip_address`, `collected_data`, `collection_date`) VALUES
('192.168.1.105', '{"essential":{"ip_address":"192.168.1.105","timestamp":"2025-07-11 09:15:00","page_url":"/index"}}', NOW() - INTERVAL 2 HOUR),
('192.168.1.106', '{"essential":{"ip_address":"192.168.1.106","timestamp":"2025-07-11 09:45:00","page_url":"/shop"},"analytics":{"user_agent":"Mozilla/5.0 (iPhone)","referer":"https://google.com"}}', NOW() - INTERVAL 1 HOUR),
('192.168.1.107', '{"essential":{"ip_address":"192.168.1.107","timestamp":"2025-07-11 10:30:00","page_url":"/cart"},"preferences":{"language":"en"}}', NOW() - INTERVAL 30 MINUTE);

-- Vérification des données insérées
SELECT 'Cookie Consents' as table_name, COUNT(*) as count FROM cookie_consents
UNION ALL
SELECT 'User Data Collection' as table_name, COUNT(*) as count FROM user_data_collection
UNION ALL
SELECT 'Data Deletion Requests' as table_name, COUNT(*) as count FROM data_deletion_requests
UNION ALL
SELECT 'RGPD Action Logs' as table_name, COUNT(*) as count FROM rgpd_action_logs;
