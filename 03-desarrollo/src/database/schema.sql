CREATE DATABASE IF NOT EXISTS documind CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE documind;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE repositories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_repositories_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repository_id INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    extension VARCHAR(10) NOT NULL,
    size_bytes INT UNSIGNED NOT NULL,
    content LONGTEXT NULL,
    category VARCHAR(80) NULL,
    summary TEXT NULL,
    extracted_data JSON NULL,
    processing_status ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    processing_error TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FULLTEXT KEY documents_content_search (original_name, content, summary),
    CONSTRAINT fk_documents_repository FOREIGN KEY (repository_id) REFERENCES repositories(id) ON DELETE CASCADE
);

CREATE TABLE processing_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id INT UNSIGNED NULL,
    level ENUM('info', 'warning', 'error') NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_processing_logs_document FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL
);

INSERT INTO users (name, email, password_hash, role)
VALUES ('Administrador', 'admin@documind.local', '$2y$10$B0bIND7fE2Uh5pu7eEMQy.ALMr7umTFTohYWarX3k/4g7eJ1/I6XG', 'admin');

INSERT INTO repositories (user_id, name, description)
SELECT id, 'Repositorio general', 'Documentos de prueba del proyecto integrador'
FROM users WHERE email = 'admin@documind.local';