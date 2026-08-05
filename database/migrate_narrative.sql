-- =====================================================================
-- SkillSync AI — Migrasi: tambah kolom narrative ke skill_profiles
-- Jalankan HANYA jika database kamu sudah pernah di-setup sebelum
-- update ini. Instalasi baru via schema.sql atau setup.php sudah
-- otomatis menyertakan kolom ini, jadi migrasi ini tidak perlu dijalankan.
--
-- Jalankan: mysql -u root -p skillsync_ai < database/migrate_narrative.sql
-- =====================================================================

ALTER TABLE skill_profiles
    ADD COLUMN IF NOT EXISTS narrative TEXT DEFAULT NULL
    COMMENT 'ringkasan naratif dari Agent Profile Generator untuk mitra'
    AFTER weaknesses;
