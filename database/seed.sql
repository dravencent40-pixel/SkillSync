-- =====================================================================
-- SkillSync AI — Seed Data (opsional, untuk demo)
-- Jalankan setelah schema.sql
-- =====================================================================
USE skillsync_ai;

INSERT INTO task_categories (name, slug, submission_type, rubric_criteria) VALUES
('Web Development', 'web-development', 'code', JSON_ARRAY(
    JSON_OBJECT('key','clean_code','label','Clean Code','description','Penamaan, struktur, komentar, konsistensi'),
    JSON_OBJECT('key','security','label','Keamanan','description','SQL Injection, XSS, validasi input, secret hardcoded'),
    JSON_OBJECT('key','efficiency','label','Efisiensi','description','Kompleksitas, query N+1, redundansi')
)),
('Data & Backend', 'data-backend', 'code', JSON_ARRAY(
    JSON_OBJECT('key','clean_code','label','Clean Code','description','Penamaan, struktur, komentar, konsistensi'),
    JSON_OBJECT('key','security','label','Keamanan','description','SQL Injection, XSS, validasi input, secret hardcoded'),
    JSON_OBJECT('key','efficiency','label','Efisiensi','description','Kompleksitas, query N+1, redundansi')
)),
('Keamanan Aplikasi', 'keamanan-aplikasi', 'code', JSON_ARRAY(
    JSON_OBJECT('key','clean_code','label','Clean Code','description','Penamaan, struktur, komentar, konsistensi'),
    JSON_OBJECT('key','security','label','Keamanan','description','SQL Injection, XSS, validasi input, secret hardcoded'),
    JSON_OBJECT('key','efficiency','label','Efisiensi','description','Kompleksitas, query N+1, redundansi')
)),
('Mobile & UI', 'mobile-ui', 'code', JSON_ARRAY(
    JSON_OBJECT('key','clean_code','label','Clean Code','description','Penamaan, struktur, komentar, konsistensi'),
    JSON_OBJECT('key','security','label','Keamanan','description','SQL Injection, XSS, validasi input, secret hardcoded'),
    JSON_OBJECT('key','efficiency','label','Efisiensi','description','Kompleksitas, query N+1, redundansi')
)),
('UI/UX Design', 'ui-ux-design', 'design', JSON_ARRAY(
    JSON_OBJECT('key','visual_hierarchy','label','Hierarki & Layout Visual','description','Alur mata, whitespace, tipografi, grid'),
    JSON_OBJECT('key','design_consistency','label','Konsistensi Design System','description','Komponen reusable, spacing/warna konsisten'),
    JSON_OBJECT('key','usability','label','Usability & Aksesibilitas','description','Kemudahan pengguna, kontras warna, ukuran tap target')
)),
('Jaringan & Infrastruktur', 'jaringan-infrastruktur', 'network', JSON_ARRAY(
    JSON_OBJECT('key','topology_design','label','Desain Topologi & Perencanaan','description','Pemilihan perangkat, alokasi IP/VLAN, redundansi'),
    JSON_OBJECT('key','configuration','label','Konfigurasi & Implementasi','description','Ketepatan konfigurasi perangkat, keamanan akses'),
    JSON_OBJECT('key','documentation','label','Dokumentasi & Troubleshooting','description','Kejelasan dokumentasi, rencana penanganan gangguan')
));

-- Akun demo — password untuk semua akun demo: "password123"
-- Hash di bawah dibuat dengan password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO users (name, email, password_hash, role, avatar_initial) VALUES
('Admin Goodeva', 'admin@goodeva.tech', '$2y$10$eD5y370M20qx2xQqy54Vo.xT.YbNz37.XhMhQXeL.eo2r5gQN81n.', 'mitra', 'AG'),
('Rafi Pratama', 'rafi@smkn9bekasi.sch.id', '$2y$10$eD5y370M20qx2xQqy54Vo.xT.YbNz37.XhMhQXeL.eo2r5gQN81n.', 'siswa', 'RP'),
('Sinta Ayu', 'sinta@smkn9bekasi.sch.id', '$2y$10$eD5y370M20qx2xQqy54Vo.xT.YbNz37.XhMhQXeL.eo2r5gQN81n.', 'siswa', 'SA');

INSERT INTO company_profiles (user_id, company_name, industry, website, about) VALUES
(1, 'Goodeva Technology', 'Software House', 'https://goodeva.tech', 'Mitra industri untuk penyaluran talenta magang SMK.');

INSERT INTO student_profiles (user_id, nis, sekolah, jurusan, kelas) VALUES
(2, '2024001', 'SMKN 9 Bekasi', 'Rekayasa Perangkat Lunak', 'XII RPL 1'),
(3, '2024002', 'SMKN 9 Bekasi', 'Rekayasa Perangkat Lunak', 'XII RPL 2');

INSERT INTO tasks (category_id, created_by, title, slug, industry_context, case_brief, starter_code, difficulty) VALUES
(1, 1, 'Perbaiki Form Login yang Rentan SQL Injection', 'perbaiki-form-login-rentan-sqli', 'Fintech',
 'Tim keamanan menemukan endpoint login pada sistem internal masih membangun query dengan menggabungkan input pengguna secara langsung. Tugasmu: refactor kode berikut agar aman dari SQL Injection dan tetap berfungsi normal, sertakan penjelasan singkat perubahan yang dilakukan.',
 '<?php\n// login.php\n$username = $_POST[\'username\'];\n$password = $_POST[\'password\'];\n$query = "SELECT * FROM users WHERE username = \'$username\' AND password = \'$password\'";\n$result = mysqli_query($conn, $query);\n',
 'menengah'),
(2, 1, 'Optimasi Query Laporan Penjualan yang Lambat', 'optimasi-query-laporan-penjualan', 'E-commerce',
 'Dashboard laporan penjualan mitra memuat data dalam waktu lebih dari 8 detik karena query N+1 di dalam loop. Refactor kode agar jumlah query berkurang drastis tanpa mengubah hasil akhir laporan.',
 '<?php\n$orders = $db->query("SELECT * FROM orders")->fetchAll();\nforeach ($orders as $order) {\n    $items = $db->query("SELECT * FROM order_items WHERE order_id = " . $order[\'id\'])->fetchAll();\n    $order[\'items\'] = $items;\n}\n',
 'menengah'),
(1, 1, 'Bangun Komponen Validasi Form Registrasi', 'komponen-validasi-form-registrasi', 'Startup',
 'Buat fungsi validasi sisi server untuk form registrasi (nama, email, password) dengan pesan error yang jelas per-field, mengikuti prinsip clean code.',
 '<?php\nfunction validateRegistration($data) {\n    // TODO: implementasikan validasi\n}\n',
 'pemula'),
(5, 1, 'Redesain Halaman Checkout E-commerce yang Membingungkan', 'redesain-checkout-ecommerce', 'E-commerce',
 'Tim produk menerima banyak keluhan pengguna yang batal checkout karena alur pembayaran membingungkan dan CTA tidak jelas. Tugasmu: desain ulang halaman checkout (mobile-first) di Figma yang menyelesaikan masalah hierarki visual dan usability tersebut, lalu jelaskan alasan keputusan desainmu.',
 NULL, 'menengah'),
(6, 1, 'Rancang Topologi Jaringan Kantor Cabang Baru', 'topologi-jaringan-kantor-cabang', 'Retail',
 'Perusahaan membuka kantor cabang baru dengan 30 karyawan, butuh pemisahan VLAN untuk divisi kasir/gudang/kantor, serta koneksi failover ke kantor pusat. Buat rancangan topologi, alokasi IP/VLAN, dan dokumentasi konfigurasi perangkat utamanya (router/switch/access point).',
 NULL, 'menengah');
