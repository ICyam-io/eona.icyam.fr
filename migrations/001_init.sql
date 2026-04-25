-- Migration 001 — Création du schéma initial EonA
-- Migration 001 — Initial EonA schema creation
-- 2026-04-25

-- Utilisateurs et profil
-- Users and profile
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED        AUTO_INCREMENT PRIMARY KEY,
    prenom          VARCHAR(100)        NOT NULL,
    nom             VARCHAR(100)        NOT NULL,
    email           VARCHAR(255)        NOT NULL UNIQUE,
    password_hash   VARCHAR(255)        NOT NULL,
    date_naissance  DATE                NOT NULL,
    sexe            ENUM('M','F')       NOT NULL,
    taille_cm       SMALLINT UNSIGNED   NOT NULL,
    poids_initial   DECIMAL(5,2)        NOT NULL,
    poids_objectif  DECIMAL(5,2)        NULL,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions PHP persistées en BDD pour survivre aux redémarrages Docker
-- PHP sessions persisted in DB to survive Docker restarts
CREATE TABLE IF NOT EXISTS sessions (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    token       VARCHAR(255)    NOT NULL UNIQUE,
    expires_at  DATETIME        NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données journalières — onglet 1
-- Daily data — tab 1
CREATE TABLE IF NOT EXISTS daily_logs (
    id                      INT UNSIGNED        AUTO_INCREMENT PRIMARY KEY,
    user_id                 INT UNSIGNED        NOT NULL,
    log_date                DATE                NOT NULL,
    type_journee            ENUM('travail_sedentaire','travail_actif','repos','sport',
                                 'fete','vacances','maladie','stress','voyage') NOT NULL,
    eveil_min               SMALLINT UNSIGNED   NULL,
    sommeil_paradoxal_min   SMALLINT UNSIGNED   NULL,
    sommeil_lent_min        SMALLINT UNSIGNED   NULL,
    sommeil_profond_min     SMALLINT UNSIGNED   NULL,
    score_sommeil           TINYINT UNSIGNED    NULL,
    score_sommeil_source    ENUM('calcule','manuel') NULL,
    poids_jour              DECIMAL(5,2)        NULL,
    calories_exercice       SMALLINT UNSIGNED   NULL,
    nb_pas                  INT UNSIGNED        NULL,
    created_at              DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_date (user_id, log_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Repas — onglet 2 bloc Repas
-- Meals — tab 2 Meal block
CREATE TABLE IF NOT EXISTS meals (
    id               INT UNSIGNED        AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED        NOT NULL,
    log_date         DATE                NOT NULL,
    description_user TEXT                NULL,
    description_ia   VARCHAR(500)        NULL,
    kcal_min         SMALLINT UNSIGNED   NULL,
    kcal_max         SMALLINT UNSIGNED   NULL,
    kcal_final       SMALLINT UNSIGNED   NULL,
    source           ENUM('gpt4v','manuel','gpt4v_nocturne','pending') NOT NULL DEFAULT 'pending',
    off_product_id   VARCHAR(100)        NULL,
    created_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_date (user_id, log_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tension artérielle — onglet 2 bloc Tension
-- Blood pressure — tab 2 Tension block
CREATE TABLE IF NOT EXISTS blood_pressure (
    id          INT UNSIGNED        AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED        NOT NULL,
    log_date    DATE                NOT NULL,
    systolique  SMALLINT UNSIGNED   NOT NULL,
    diastolique SMALLINT UNSIGNED   NOT NULL,
    bpm         SMALLINT UNSIGNED   NOT NULL,
    created_at  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_date (user_id, log_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analyses GPT-4o en attente de traitement nocturne
-- GPT-4o analyses pending nightly processing
CREATE TABLE IF NOT EXISTS pending_analyses (
    id               INT UNSIGNED        AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED        NOT NULL,
    meal_id          INT UNSIGNED        NOT NULL,
    photo_path       VARCHAR(500)        NOT NULL,
    description_user TEXT                NULL,
    attempts         TINYINT UNSIGNED    NOT NULL DEFAULT 0,
    status           ENUM('pending','processed','failed') NOT NULL DEFAULT 'pending',
    created_at       DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at     DATETIME            NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_id) REFERENCES meals(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
