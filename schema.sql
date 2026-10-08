-- data-cleaner/schema.sql
-- Schéma optionnel pour ajouter un historique des nettoyages
-- À exécuter dans phpMyAdmin si tu veux persenter l'historique des opérations

CREATE TABLE IF NOT EXISTS cleaning_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_name VARCHAR(255) NOT NULL,
    rows_processed INT NOT NULL,
    duplicates_removed INT DEFAULT 0,
    empty_rows_removed INT DEFAULT 0,
    columns_removed INT DEFAULT 0,
    dates_standardized INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_ip VARCHAR(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index pour améliorer les requêtes sur l'historique
CREATE INDEX IF NOT EXISTS idx_created_at ON cleaning_history(created_at);