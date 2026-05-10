-- ============================================================
--  Sistema de Recomendação de Filmes — Schema da Base de Dados
-- ============================================================

CREATE DATABASE IF NOT EXISTS movie_recommendation
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE movie_recommendation;

-- ------------------------------------------------------------
-- Tabela: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)        NOT NULL,
    email         VARCHAR(150)        NOT NULL UNIQUE,
    password_hash VARCHAR(255)        NOT NULL,
    role          ENUM('user','admin') NOT NULL DEFAULT 'user',
    avatar_url    VARCHAR(500)        NULL,
    preferred_lang ENUM('pt','en')    NOT NULL DEFAULT 'pt',
    theme         ENUM('light','dark') NOT NULL DEFAULT 'light',
    reset_token   VARCHAR(255)        NULL,
    reset_token_expires_at DATETIME   NULL,
    created_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabela: movies_cache  (evita chamadas repetidas à TMDB API)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS movies_cache (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tmdb_id       INT UNSIGNED        NOT NULL UNIQUE,
    title         VARCHAR(300)        NOT NULL,
    original_title VARCHAR(300)       NULL,
    overview      TEXT                NULL,
    poster_path   VARCHAR(500)        NULL,
    backdrop_path VARCHAR(500)        NULL,
    release_date  DATE                NULL,
    genres        JSON                NULL,
    vote_average  DECIMAL(4,2)        NULL,
    vote_count    INT UNSIGNED        NULL,
    popularity    DECIMAL(10,3)       NULL,
    cached_at     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tmdb_id (tmdb_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabela: ratings  (avaliações do utilizador)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ratings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED        NOT NULL,
    tmdb_id       INT UNSIGNED        NOT NULL,
    score         TINYINT UNSIGNED    NOT NULL CHECK (score BETWEEN 1 AND 10),
    review        TEXT                NULL,
    created_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_movie (user_id, tmdb_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabela: watchlist  (filmes para ver mais tarde)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS watchlist (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED        NOT NULL,
    tmdb_id       INT UNSIGNED        NOT NULL,
    watched       TINYINT(1)          NOT NULL DEFAULT 0,
    added_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    watched_at    DATETIME            NULL,
    UNIQUE KEY uq_user_movie (user_id, tmdb_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabela: favorites
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS favorites (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED        NOT NULL,
    tmdb_id       INT UNSIGNED        NOT NULL,
    added_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_movie (user_id, tmdb_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabela: genre_preferences  (para melhorar recomendações)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS genre_preferences (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED        NOT NULL,
    genre_id      INT UNSIGNED        NOT NULL,
    genre_name    VARCHAR(100)        NOT NULL,
    weight        DECIMAL(5,2)        NOT NULL DEFAULT 1.00,
    UNIQUE KEY uq_user_genre (user_id, genre_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Admin padrão (senha: Admin@1234)
-- ------------------------------------------------------------
INSERT INTO users (name, email, password_hash, role) VALUES
('Administrador', 'admin@movies.ao',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
