-- =====================================================================
-- SkillSync AI — Migrasi: CV terhubung ke akun siswa
--
-- Sebelumnya upload_cv.php menyimpan CV di file JSON terpisah
-- (uploads/cvs/metadata.json) dengan nama/email yang diketik manual —
-- SAMA SEKALI tidak terhubung ke akun/users di database. Ini yang
-- menyebabkan Talent Pool (company/talent.php) menampilkan data yang
-- berbeda dari skill_profiles/dashboard (yang membaca dari database asli).
--
-- Migrasi ini memindahkan CV jadi milik akun siswa yang login, disimpan
-- di kolom berikut (bukan lagi file JSON terpisah).
--
-- Jalankan: mysql -u root -p skillsync_ai < database/migrate_accounts_cv.sql
-- =====================================================================
USE skillsync_ai;

ALTER TABLE student_profiles
    ADD COLUMN IF NOT EXISTS cv_path VARCHAR(255) DEFAULT NULL COMMENT 'path relatif file CV milik siswa ini' AFTER github_url,
    ADD COLUMN IF NOT EXISTS cv_original_name VARCHAR(255) DEFAULT NULL AFTER cv_path,
    ADD COLUMN IF NOT EXISTS cv_uploaded_at DATETIME DEFAULT NULL AFTER cv_original_name;
