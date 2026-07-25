-- =====================================================================
-- SkillSync AI — Database Schema (MySQL 8+/MariaDB 10.5+)
-- AI Technical Project Lead & Assessment Agent untuk SMK/Perusahaan Tech
-- =====================================================================
-- Import: mysql -u root -p skillsync_ai < schema.sql
-- =====================================================================

CREATE DATABASE IF NOT EXISTS skillsync_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE skillsync_ai;

-- ---------------------------------------------------------------------
-- 1. USERS — akun inti, dua peran: siswa (talent) & mitra (perusahaan/sekolah)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)        NOT NULL,
    email           VARCHAR(150)        NOT NULL UNIQUE,
    password_hash   VARCHAR(255)        NOT NULL,
    role            ENUM('siswa','mitra') NOT NULL DEFAULT 'siswa',
    avatar_initial  VARCHAR(4)          DEFAULT NULL,
    is_active       TINYINT(1)          NOT NULL DEFAULT 1,
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. STUDENT_PROFILES — data tambahan khusus siswa SMK
-- ---------------------------------------------------------------------
CREATE TABLE student_profiles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL UNIQUE,
    nis         VARCHAR(30)  DEFAULT NULL,
    sekolah     VARCHAR(150) DEFAULT 'SMKN 9 Bekasi',
    jurusan     VARCHAR(100) DEFAULT NULL,
    kelas       VARCHAR(20)  DEFAULT NULL,
    bio         TEXT         DEFAULT NULL,
    github_url  VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. COMPANY_PROFILES — data tambahan khusus mitra/perusahaan
-- ---------------------------------------------------------------------
CREATE TABLE company_profiles (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL UNIQUE,
    company_name  VARCHAR(150) NOT NULL,
    industry      VARCHAR(100) DEFAULT NULL,
    website       VARCHAR(255) DEFAULT NULL,
    about         TEXT         DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. TASK_CATEGORIES — kategori studi kasus (dipakai untuk skor per-bidang)
-- ---------------------------------------------------------------------
CREATE TABLE task_categories (
    id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(60) NOT NULL UNIQUE,
    slug  VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. TASKS — studi kasus/kodingan riil yang dikeluarkan Agent Task Issuer
-- ---------------------------------------------------------------------
CREATE TABLE tasks (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id       INT UNSIGNED NOT NULL,
    created_by        INT UNSIGNED DEFAULT NULL COMMENT 'user_id mitra pembuat, NULL = auto-generated agent',
    title             VARCHAR(180) NOT NULL,
    slug              VARCHAR(200) NOT NULL UNIQUE,
    industry_context  VARCHAR(150) DEFAULT NULL COMMENT 'mis: Fintech, E-commerce, Logistik',
    case_brief        TEXT         NOT NULL COMMENT 'deskripsi studi kasus dari agent',
    starter_code      MEDIUMTEXT   DEFAULT NULL,
    difficulty        ENUM('pemula','menengah','mahir') NOT NULL DEFAULT 'pemula',
    is_active         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES task_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. SUBMISSIONS — jawaban/kode siswa untuk sebuah task
-- ---------------------------------------------------------------------
CREATE TABLE submissions (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id        INT UNSIGNED NOT NULL,
    user_id        INT UNSIGNED NOT NULL,
    language       VARCHAR(30)  NOT NULL DEFAULT 'php',
    code_content   MEDIUMTEXT   NOT NULL,
    notes          TEXT         DEFAULT NULL COMMENT 'catatan siswa ke reviewer',
    status         ENUM('submitted','reviewed','revised') NOT NULL DEFAULT 'submitted',
    submitted_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_task (user_id, task_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. AI_REVIEWS — hasil Agent Reviewer & Auditor (1:1 dengan submission)
-- ---------------------------------------------------------------------
CREATE TABLE ai_reviews (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_id       INT UNSIGNED NOT NULL UNIQUE,
    clean_code_score    TINYINT UNSIGNED NOT NULL COMMENT '0-100',
    security_score      TINYINT UNSIGNED NOT NULL COMMENT '0-100',
    efficiency_score    TINYINT UNSIGNED NOT NULL COMMENT '0-100',
    overall_score       TINYINT UNSIGNED NOT NULL COMMENT '0-100, rata-rata tertimbang',
    summary             TEXT             NOT NULL,
    findings_json       JSON             DEFAULT NULL COMMENT 'daftar temuan: [{severity,title,detail,line}]',
    reviewed_at         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. MENTOR_CONVERSATIONS — sesi chat dengan Agent Mentor
-- ---------------------------------------------------------------------
CREATE TABLE mentor_conversations (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_id  INT UNSIGNED DEFAULT NULL,
    user_id        INT UNSIGNED NOT NULL,
    title          VARCHAR(150) DEFAULT 'Sesi Mentoring',
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. MENTOR_MESSAGES — isi percakapan
-- ---------------------------------------------------------------------
CREATE TABLE mentor_messages (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id  INT UNSIGNED NOT NULL,
    sender           ENUM('user','agent') NOT NULL,
    message          TEXT NOT NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES mentor_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. SKILL_PROFILES — hasil agregasi Agent Profile Generator (1:1 user siswa)
-- ---------------------------------------------------------------------
CREATE TABLE skill_profiles (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED NOT NULL UNIQUE,
    overall_score     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    clean_code_avg    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    security_avg      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    efficiency_avg    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    tasks_completed    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    badge             ENUM('Pemula','Junior Ready','Job Ready','Top Talent') NOT NULL DEFAULT 'Pemula',
    strengths         VARCHAR(255) DEFAULT NULL,
    weaknesses        VARCHAR(255) DEFAULT NULL,
    narrative         TEXT         DEFAULT NULL COMMENT 'ringkasan naratif dari Agent Profile Generator untuk mitra',
    is_public         TINYINT(1)   NOT NULL DEFAULT 1 COMMENT 'siswa mengizinkan profil dilihat mitra',
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. RECOMMENDATIONS — mitra menandai/melacak talenta yang diminati
-- ---------------------------------------------------------------------
CREATE TABLE recommendations (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id   INT UNSIGNED NOT NULL COMMENT 'company_profiles.id',
    user_id      INT UNSIGNED NOT NULL COMMENT 'siswa yang direkomendasikan',
    status       ENUM('disimpan','dihubungi','interview','magang') NOT NULL DEFAULT 'disimpan',
    note         TEXT DEFAULT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES company_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_company_student (company_id, user_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12. ACTIVITY_LOGS — audit trail ringan
-- ---------------------------------------------------------------------
CREATE TABLE activity_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED DEFAULT NULL,
    action      VARCHAR(100) NOT NULL,
    meta        VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
