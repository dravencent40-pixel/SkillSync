-- =====================================================================
-- SkillSync AI — Migrasi: Multi-Track Assessment
-- Menggeneralisasi sistem dari "hanya kode" menjadi rubric-driven untuk
-- divisi apa pun (Programming, UI/UX Design, Jaringan, dst).
--
-- Jalankan HANYA jika database kamu sudah pernah di-setup sebelum
-- update ini. Instalasi baru via schema.sql sudah otomatis menyertakan ini.
--
-- Jalankan: mysql -u root -p skillsync_ai < database/migrate_multitrack.sql
-- =====================================================================
USE skillsync_ai;

-- ---------------------------------------------------------------------
-- 1. task_categories — tandai tipe submission & kriteria rubric per divisi.
--    3 slot skor (clean_code_score/security_score/efficiency_score di
--    ai_reviews) TETAP dipakai sebagai slot generik — cuma LABEL & makna
--    kriterianya yang berbeda per kategori, diambil dari rubric_criteria.
--    Ini sengaja dipilih supaya tidak perlu rename kolom di seluruh
--    aplikasi (submission.php, dashboard.php, ProfileGeneratorAgent, dst).
-- ---------------------------------------------------------------------
ALTER TABLE task_categories
    ADD COLUMN IF NOT EXISTS submission_type ENUM('code','design','network','general') NOT NULL DEFAULT 'code'
    COMMENT 'menentukan bentuk form submission & gaya evaluasi Agent Reviewer' AFTER slug,
    ADD COLUMN IF NOT EXISTS rubric_criteria JSON DEFAULT NULL
    COMMENT '3 kriteria penilaian: [{"key":"...","label":"...","description":"..."}]' AFTER submission_type;

-- Kategori kode yang sudah ada: kunci rubric_criteria supaya label yang
-- tampil di UI (Clean Code/Keamanan/Efisiensi) tidak berubah dari sebelumnya.
UPDATE task_categories SET
    submission_type = 'code',
    rubric_criteria = JSON_ARRAY(
        JSON_OBJECT('key','clean_code','label','Clean Code','description','Penamaan, struktur, komentar, konsistensi'),
        JSON_OBJECT('key','security','label','Keamanan','description','SQL Injection, XSS, validasi input, secret hardcoded'),
        JSON_OBJECT('key','efficiency','label','Efisiensi','description','Kompleksitas, query N+1, redundansi')
    )
WHERE submission_type = 'code' AND rubric_criteria IS NULL;

-- ---------------------------------------------------------------------
-- 2. submissions — dukung link eksternal (mis. Figma) dan file (mis.
--    screenshot desain / topologi jaringan) selain teks/kode.
-- ---------------------------------------------------------------------
ALTER TABLE submissions
    ADD COLUMN IF NOT EXISTS file_path VARCHAR(255) DEFAULT NULL COMMENT 'path relatif file yang diunggah (screenshot desain, dsb)' AFTER code_content,
    ADD COLUMN IF NOT EXISTS external_link VARCHAR(500) DEFAULT NULL COMMENT 'link eksternal, mis. Figma/dokumen' AFTER file_path;

-- Catatan: kolom `language` yang sudah ada tetap dipakai untuk menandai jenis
-- submission spesifik (mis. 'php', 'figma-link', 'network-config'), tidak diubah.

-- ---------------------------------------------------------------------
-- 3. Kategori baru: UI/UX Design & Jaringan/Infrastruktur
-- ---------------------------------------------------------------------
INSERT INTO task_categories (name, slug, submission_type, rubric_criteria)
SELECT 'UI/UX Design', 'ui-ux-design', 'design', JSON_ARRAY(
    JSON_OBJECT('key','visual_hierarchy','label','Hierarki & Layout Visual','description','Alur mata, penggunaan whitespace, tipografi, grid'),
    JSON_OBJECT('key','design_consistency','label','Konsistensi Design System','description','Komponen reusable, spacing/warna konsisten'),
    JSON_OBJECT('key','usability','label','Usability & Aksesibilitas','description','Kemudahan pengguna, kontras warna, ukuran tap target')
)
WHERE NOT EXISTS (SELECT 1 FROM task_categories WHERE slug = 'ui-ux-design');

INSERT INTO task_categories (name, slug, submission_type, rubric_criteria)
SELECT 'Jaringan & Infrastruktur', 'jaringan-infrastruktur', 'network', JSON_ARRAY(
    JSON_OBJECT('key','topology_design','label','Desain Topologi & Perencanaan','description','Pemilihan perangkat, alokasi IP/VLAN, redundansi'),
    JSON_OBJECT('key','configuration','label','Konfigurasi & Implementasi','description','Ketepatan konfigurasi perangkat, keamanan akses'),
    JSON_OBJECT('key','documentation','label','Dokumentasi & Troubleshooting','description','Kejelasan dokumentasi, rencana penanganan gangguan')
)
WHERE NOT EXISTS (SELECT 1 FROM task_categories WHERE slug = 'jaringan-infrastruktur');
