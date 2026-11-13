-- Creates the table the app needs.
--
-- Shared hosting (cPanel / Hostinger): first create the database in the control panel,
-- then open phpMyAdmin, select that database, and Import this file.
--
-- Your own server:  mysql -u root -p -e "CREATE DATABASE engagement CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
--                   mysql -u root -p engagement < schema.sql

CREATE TABLE IF NOT EXISTS wishes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(80)   NOT NULL,
    message     VARCHAR(500)  NOT NULL,
    photo       VARCHAR(120)  NULL,
    ip_address  VARCHAR(45)   NULL,
    user_agent  VARCHAR(255)  NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
