-- =====================================================================
-- SkillSync — Migrasi: Skor Per-Divisi (skill_profile_tracks)
--
-- Masalah yang diperbaiki: skill_profiles.clean_code_avg/security_avg/
-- efficiency_avg merata-ratakan SEMUA submission siswa jadi satu angka,
-- padahal kolom itu jadi slot generik untuk rubric_criteria[0/1/2] yang
-- BEDA MAKNA per kategori sejak migrate_multitrack.sql (mis. "Clean Code"
-- punya slot yang sama dengan "Hierarki Visual" di UI/UX Design). Rata-rata
-- lintas divisi yang berbeda makna itu tidak informatif.
--
-- Solusi: simpan rata-rata TERPISAH per (siswa, kategori) di tabel baru ini.
-- skill_profiles TETAP ada sebagai ringkasan lintas-divisi (dipakai untuk
-- badge & pencarian talenta mitra), tapi dashboard siswa sekarang menampilkan
-- breakdown per divisi dari tabel ini, bukan satu rata-rata yang membaurkan.
--
-- Jalankan: mysql -u root -p skillsync < database/migrate_track_scores.sql
-- =====================================================================

CREATE TABLE IF NOT EXISTS skill_profile_tracks (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id            INT UNSIGNED NOT NULL,
    category_id        INT UNSIGNED NOT NULL,
    overall_score      TINYINT UNSIGNED NOT NULL DEFAULT 0,
    criterion1_score   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    criterion2_score   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    criterion3_score   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    comprehension_avg  TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'rata-rata skor Agent Defense KHUSUS kategori ini',
    tasks_completed    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_category (user_id, category_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES task_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB;
