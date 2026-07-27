-- =====================================================================
-- SkillSync AI — Migrasi: Agent Defense (sesi pembelaan project)
-- Jalankan HANYA jika database kamu sudah pernah di-setup sebelum
-- update ini. Instalasi baru via schema.sql atau setup.php sudah
-- otomatis menyertakan tabel ini, jadi migrasi ini tidak perlu dijalankan.
--
-- Jalankan: mysql -u root -p skillsync_ai < database/migrate_defense.sql
-- =====================================================================
USE skillsync_ai;

-- Skor pemahaman rata-rata siswa, ikut membentuk overall_score di Profile Generator.
ALTER TABLE skill_profiles
    ADD COLUMN IF NOT EXISTS comprehension_avg TINYINT UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'rata-rata skor sesi Agent Defense — indikasi pemahaman nyata, bukan sekadar kualitas kode'
    AFTER efficiency_avg;

CREATE TABLE IF NOT EXISTS defense_sessions (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_id        INT UNSIGNED NOT NULL UNIQUE,
    status               ENUM('pending','answered','evaluated') NOT NULL DEFAULT 'pending',
    comprehension_score  TINYINT UNSIGNED DEFAULT NULL,
    feedback             TEXT DEFAULT NULL,
    ai_assisted          TINYINT(1) NOT NULL DEFAULT 0,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    answered_at          DATETIME DEFAULT NULL,
    evaluated_at         DATETIME DEFAULT NULL,
    FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS defense_questions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id      INT UNSIGNED NOT NULL,
    order_index     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    question        TEXT NOT NULL,
    answer          TEXT DEFAULT NULL,
    answer_score    TINYINT UNSIGNED DEFAULT NULL,
    answer_feedback TEXT DEFAULT NULL,
    FOREIGN KEY (session_id) REFERENCES defense_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB;
