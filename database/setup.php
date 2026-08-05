<?php
/**
 * SkillSync AI — Database Setup Script
 * 
 * Jalankan sekali dari browser: http://localhost:8000/database/setup.php
 * atau dari CLI: php database/setup.php
 * 
 * Script ini akan:
 * 1. Membuat database skillsync_ai
 * 2. Membuat semua tabel (12 tabel)
 * 3. Mengisi seed data (akun demo, kategori, studi kasus)
 * 4. Memverifikasi setup
 */

$isCLI = php_sapi_name() === 'cli';

function out($msg, $isCLI) {
    if ($isCLI) {
        echo $msg . "\n";
    } else {
        echo $msg . "<br>\n";
    }
}

if (!$isCLI) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>SkillSync AI - Database Setup</title>';
    echo '<style>body{font-family:monospace;max-width:800px;margin:2rem auto;padding:0 1rem;background:#0f172a;color:#e2e8f0;line-height:1.8;}';
    echo '.ok{color:#10b981;}.err{color:#ef4444;}.info{color:#3b82f6;}.title{font-size:1.5rem;font-weight:bold;margin-bottom:1rem;color:#fff;}';
    echo '.card{background:#1e293b;padding:1.5rem;border-radius:1rem;margin:1rem 0;border:1px solid #334155;}';
    echo 'code{background:#334155;padding:2px 6px;border-radius:4px;font-size:0.9em;}';
    echo '</style></head><body>';
    echo '<div class="title">◎ SkillSync AI — Database Setup</div>';
}

out("", $isCLI);

// ============================================================
// Step 1: Connect to MySQL (without selecting a database)
// ============================================================
out("Step 1: Menghubungkan ke MySQL...", $isCLI);

try {
    $pdo = new PDO(
        'mysql:host=localhost;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    out('<span class="ok">✓ Berhasil terhubung ke MySQL</span>', $isCLI);
} catch (PDOException $e) {
    out('<span class="err">✗ Gagal koneksi: ' . $e->getMessage() . '</span>', $isCLI);
    out("Pastikan MySQL/MariaDB berjalan di XAMPP Control Panel.", $isCLI);
    if (!$isCLI) echo '</body></html>';
    exit(1);
}

out("", $isCLI);

// ============================================================
// Step 2: Create database
// ============================================================
out("Step 2: Membuat database skillsync_ai...", $isCLI);

try {
    $pdo->exec("DROP DATABASE IF EXISTS skillsync_ai");
    $pdo->exec("CREATE DATABASE skillsync_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE skillsync_ai");
    out('<span class="ok">✓ Database skillsync_ai berhasil dibuat</span>', $isCLI);
} catch (PDOException $e) {
    out('<span class="err">✗ Gagal membuat database: ' . $e->getMessage() . '</span>', $isCLI);
    if (!$isCLI) echo '</body></html>';
    exit(1);
}

out("", $isCLI);

// ============================================================
// Step 3: Create tables (12 tabel)
// ============================================================
out("Step 3: Membuat 12 tabel...", $isCLI);

$queries = [
    // 1. USERS
    "CREATE TABLE users (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name            VARCHAR(100)        NOT NULL,
        email           VARCHAR(150)        NOT NULL UNIQUE,
        password_hash   VARCHAR(255)        NOT NULL,
        role            ENUM('siswa','mitra') NOT NULL DEFAULT 'siswa',
        avatar_initial  VARCHAR(4)          DEFAULT NULL,
        is_active       TINYINT(1)          NOT NULL DEFAULT 1,
        created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    // 2. STUDENT_PROFILES
    "CREATE TABLE student_profiles (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id     INT UNSIGNED NOT NULL UNIQUE,
        nis         VARCHAR(30)  DEFAULT NULL,
        sekolah     VARCHAR(150) DEFAULT 'SMKN 9 Bekasi',
        jurusan     VARCHAR(100) DEFAULT NULL,
        kelas       VARCHAR(20)  DEFAULT NULL,
        bio         TEXT         DEFAULT NULL,
        github_url  VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    // 3. COMPANY_PROFILES
    "CREATE TABLE company_profiles (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id       INT UNSIGNED NOT NULL UNIQUE,
        company_name  VARCHAR(150) NOT NULL,
        industry      VARCHAR(100) DEFAULT NULL,
        website       VARCHAR(255) DEFAULT NULL,
        about         TEXT         DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    // 4. TASK_CATEGORIES
    "CREATE TABLE task_categories (
        id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name  VARCHAR(60) NOT NULL UNIQUE,
        slug  VARCHAR(60) NOT NULL UNIQUE
    ) ENGINE=InnoDB",

    // 5. TASKS
    "CREATE TABLE tasks (
        id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_id       INT UNSIGNED NOT NULL,
        created_by        INT UNSIGNED DEFAULT NULL,
        title             VARCHAR(180) NOT NULL,
        slug              VARCHAR(200) NOT NULL UNIQUE,
        industry_context  VARCHAR(150) DEFAULT NULL,
        case_brief        TEXT         NOT NULL,
        starter_code      MEDIUMTEXT   DEFAULT NULL,
        difficulty        ENUM('pemula','menengah','mahir') NOT NULL DEFAULT 'pemula',
        is_active         TINYINT(1)   NOT NULL DEFAULT 1,
        created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES task_categories(id),
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    // 6. SUBMISSIONS
    "CREATE TABLE submissions (
        id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        task_id        INT UNSIGNED NOT NULL,
        user_id        INT UNSIGNED NOT NULL,
        language       VARCHAR(30)  NOT NULL DEFAULT 'php',
        code_content   MEDIUMTEXT   NOT NULL,
        notes          TEXT         DEFAULT NULL,
        status         ENUM('submitted','reviewed','revised') NOT NULL DEFAULT 'submitted',
        submitted_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_task (user_id, task_id)
    ) ENGINE=InnoDB",

    // 7. AI_REVIEWS
    "CREATE TABLE ai_reviews (
        id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        submission_id       INT UNSIGNED NOT NULL UNIQUE,
        clean_code_score    TINYINT UNSIGNED NOT NULL,
        security_score      TINYINT UNSIGNED NOT NULL,
        efficiency_score    TINYINT UNSIGNED NOT NULL,
        overall_score       TINYINT UNSIGNED NOT NULL,
        summary             TEXT             NOT NULL,
        findings_json       JSON             DEFAULT NULL,
        reviewed_at         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    // 8. MENTOR_CONVERSATIONS
    "CREATE TABLE mentor_conversations (
        id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        submission_id  INT UNSIGNED DEFAULT NULL,
        user_id        INT UNSIGNED NOT NULL,
        title          VARCHAR(150) DEFAULT 'Sesi Mentoring',
        created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (submission_id) REFERENCES submissions(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    // 9. MENTOR_MESSAGES
    "CREATE TABLE mentor_messages (
        id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        conversation_id  INT UNSIGNED NOT NULL,
        sender           ENUM('user','agent') NOT NULL,
        message          TEXT NOT NULL,
        created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES mentor_conversations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    // 10. SKILL_PROFILES
    "CREATE TABLE skill_profiles (
        id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id           INT UNSIGNED NOT NULL UNIQUE,
        overall_score     TINYINT UNSIGNED NOT NULL DEFAULT 0,
        clean_code_avg    TINYINT UNSIGNED NOT NULL DEFAULT 0,
        security_avg      TINYINT UNSIGNED NOT NULL DEFAULT 0,
        efficiency_avg    TINYINT UNSIGNED NOT NULL DEFAULT 0,
        tasks_completed   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        badge             ENUM('Pemula','Junior Ready','Job Ready','Top Talent') NOT NULL DEFAULT 'Pemula',
        strengths         VARCHAR(255) DEFAULT NULL,
        weaknesses        VARCHAR(255) DEFAULT NULL,
        narrative         TEXT         DEFAULT NULL,
        is_public         TINYINT(1)   NOT NULL DEFAULT 1,
        updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    // 11. RECOMMENDATIONS
    "CREATE TABLE recommendations (
        id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        company_id   INT UNSIGNED NOT NULL,
        user_id      INT UNSIGNED NOT NULL,
        status       ENUM('disimpan','dihubungi','interview','magang') NOT NULL DEFAULT 'disimpan',
        note         TEXT DEFAULT NULL,
        created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES company_profiles(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY uniq_company_student (company_id, user_id)
    ) ENGINE=InnoDB",

    // 12. ACTIVITY_LOGS
    "CREATE TABLE activity_logs (
        id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id     INT UNSIGNED DEFAULT NULL,
        action      VARCHAR(100) NOT NULL,
        meta        VARCHAR(255) DEFAULT NULL,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",
];

$tableNames = [
    'users', 'student_profiles', 'company_profiles', 'task_categories',
    'tasks', 'submissions', 'ai_reviews', 'mentor_conversations',
    'mentor_messages', 'skill_profiles', 'recommendations', 'activity_logs'
];

foreach ($queries as $i => $sql) {
    try {
        $pdo->exec($sql);
        out('<span class="ok">  ✓ ' . $tableNames[$i] . '</span>', $isCLI);
    } catch (PDOException $e) {
        out('<span class="err">  ✗ ' . $tableNames[$i] . ': ' . $e->getMessage() . '</span>', $isCLI);
    }
}

out("", $isCLI);

// ============================================================
// Step 4: Seed data
// ============================================================
out("Step 4: Mengisi seed data...", $isCLI);

// Generate fresh password hash for "password123"
$hash = password_hash('password123', PASSWORD_DEFAULT);

// 4a. Task Categories
$pdo->exec("INSERT INTO task_categories (name, slug) VALUES
    ('Web Development', 'web-development'),
    ('Data & Backend', 'data-backend'),
    ('Keamanan Aplikasi', 'keamanan-aplikasi'),
    ('Mobile & UI', 'mobile-ui')");
out('<span class="ok">  ✓ 4 task categories</span>', $isCLI);

// 4b. Users
$stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, avatar_initial) VALUES (?, ?, ?, ?, ?)");
$users = [
    ['Admin Goodeva', 'admin@goodeva.tech', $hash, 'mitra', 'AG'],
    ['Rafi Pratama', 'rafi@smkn9bekasi.sch.id', $hash, 'siswa', 'RP'],
    ['Sinta Ayu', 'sinta@smkn9bekasi.sch.id', $hash, 'siswa', 'SA'],
];
foreach ($users as $u) {
    $stmt->execute($u);
}
out('<span class="ok">  ✓ 3 users (admin + 2 siswa)</span>', $isCLI);

// 4c. Company Profile
$pdo->prepare("INSERT INTO company_profiles (user_id, company_name, industry, website, about) VALUES (?, ?, ?, ?, ?)")
    ->execute([1, 'Goodeva Technology', 'Software House', 'https://goodeva.tech', 'Mitra industri untuk penyaluran talenta magang SMK.']);
out('<span class="ok">  ✓ 1 company profile</span>', $isCLI);

// 4d. Student Profiles
$pdo->prepare("INSERT INTO student_profiles (user_id, nis, sekolah, jurusan, kelas) VALUES (?, ?, ?, ?, ?)")
    ->execute([2, '2024001', 'SMKN 9 Bekasi', 'Rekayasa Perangkat Lunak', 'XII RPL 1']);
$pdo->prepare("INSERT INTO student_profiles (user_id, nis, sekolah, jurusan, kelas) VALUES (?, ?, ?, ?, ?)")
    ->execute([3, '2024002', 'SMKN 9 Bekasi', 'Rekayasa Perangkat Lunak', 'XII RPL 2']);
out('<span class="ok">  ✓ 2 student profiles</span>', $isCLI);

// 4e. Tasks
$stmtTask = $pdo->prepare("INSERT INTO tasks (category_id, created_by, title, slug, industry_context, case_brief, starter_code, difficulty) VALUES (?,?,?,?,?,?,?,?)");

$tasks = [
    [1, 1, 'Perbaiki Form Login yang Rentan SQL Injection', 'perbaiki-form-login-rentan-sqli', 'Fintech',
     'Tim keamanan menemukan endpoint login pada sistem internal masih membangun query dengan menggabungkan input pengguna secara langsung. Tugasmu: refactor kode berikut agar aman dari SQL Injection dan tetap berfungsi normal, sertakan penjelasan singkat perubahan yang dilakukan.',
     '<?php\n// login.php\n$username = $_POST[\'username\'];\n$password = $_POST[\'password\'];\n$query = "SELECT * FROM users WHERE username = \'$username\' AND password = \'$password\'";\n$result = mysqli_query($conn, $query);\n',
     'menengah'],
    [2, 1, 'Optimasi Query Laporan Penjualan yang Lambat', 'optimasi-query-laporan-penjualan', 'E-commerce',
     'Dashboard laporan penjualan mitra memuat data dalam waktu lebih dari 8 detik karena query N+1 di dalam loop. Refactor kode agar jumlah query berkurang drastis tanpa mengubah hasil akhir laporan.',
     '<?php\n$orders = $db->query("SELECT * FROM orders")->fetchAll();\nforeach ($orders as $order) {\n    $items = $db->query("SELECT * FROM order_items WHERE order_id = " . $order[\'id\'])->fetchAll();\n    $order[\'items\'] = $items;\n}\n',
     'menengah'],
    [1, 1, 'Bangun Komponen Validasi Form Registrasi', 'komponen-validasi-form-registrasi', 'Startup',
     'Buat fungsi validasi sisi server untuk form registrasi (nama, email, password) dengan pesan error yang jelas per-field, mengikuti prinsip clean code.',
     '<?php\nfunction validateRegistration($data) {\n    // TODO: implementasikan validasi\n}\n',
     'pemula'],
];
foreach ($tasks as $t) {
    $stmtTask->execute($t);
}
out('<span class="ok">  ✓ 3 studi kasus</span>', $isCLI);

out("", $isCLI);

// ============================================================
// Step 5: Verify
// ============================================================
out("Step 5: Memverifikasi...", $isCLI);

$counts = [];
foreach ($tableNames as $table) {
    $result = $pdo->query("SELECT COUNT(*) as c FROM `$table`")->fetch();
    $counts[$table] = $result['c'];
}

out('<div class="card">', $isCLI);
out("Tabel             | Jumlah Baris", $isCLI);
out(str_repeat("-", 40), $isCLI);
foreach ($counts as $table => $count) {
    $padded = str_pad($table, 20) . "| " . str_pad($count, 5);
    out($padded, $isCLI);
}
out('</div>', $isCLI);

$total = array_sum($counts);
out('<span class="ok">✓ Setup selesai! Total: ' . $total . ' baris di ' . count($tableNames) . ' tabel</span>', $isCLI);

out("", $isCLI);
out("================================================", $isCLI);
out("AKUN DEMO:", $isCLI);
out("================================================", $isCLI);
out("", $isCLI);
out("SISWA:", $isCLI);
out("  Email    : rafi@smkn9bekasi.sch.id", $isCLI);
out("  Password : password123", $isCLI);
out("", $isCLI);
out("SISWA 2:", $isCLI);
out("  Email    : sinta@smkn9bekasi.sch.id", $isCLI);
out("  Password : password123", $isCLI);
out("", $isCLI);
out("MITRA (PERUSAHAAN):", $isCLI);
out("  Email    : admin@goodeva.tech", $isCLI);
out("  Password : password123", $isCLI);
out("", $isCLI);
out("================================================", $isCLI);
out("Buka: http://localhost:8000", $isCLI);
out("================================================", $isCLI);

if (!$isCLI) {
    echo '<div class="card" style="margin-top:2rem;">';
    echo '<div style="font-size:1.1rem;font-weight:bold;margin-bottom:1rem;">🚀 Langkah Selanjutnya</div>';
    echo '<p style="color:#94a3b8;">Database sudah siap! Sekarang:</p>';
    echo '<ol style="margin:1rem 0;padding-left:1.5rem;color:#e2e8f0;">';
    echo '<li>Buka terminal di folder project</li>';
    echo '<li>Jalankan: <code>php -S localhost:8000</code></li>';
    echo '<li>Buka browser: <a href="http://localhost:8000" style="color:#10b981;">http://localhost:8000</a></li>';
    echo '<li>Login dengan akun demo di atas</li>';
    echo '</ol>';
    echo '</div>';
    echo '</body></html>';
}
