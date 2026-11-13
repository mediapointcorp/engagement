-- Run once:  mysql -u root -p < schema.sql
CREATE DATABASE IF NOT EXISTS engagement CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE engagement;

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

-- Optional: a dedicated user for the app (change the password!)
-- CREATE USER 'engagement_user'@'localhost' IDENTIFIED BY 'change-me';
-- GRANT SELECT, INSERT, DELETE ON engagement.* TO 'engagement_user'@'localhost';
