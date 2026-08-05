<?php
/**
 * SkillSync — Seed Demo Data (via real agent pipeline)
 *
 * Mengisi data demo untuk akun siswa di database local dengan menjalankan
 * PIPELINE ASLI aplikasi (bukan insert skor karangan):
 *   1. Insert submission      -> 2. ReviewerAuditorAgent->review()
 *   3. Insert ai_reviews      -> 4. DefenseAgent->generateQuestions()
 *   5. Insert defense session -> 6. simulasi jawab -> DefenseAgent->evaluateAnswers()
 *   7. ProfileGeneratorAgent->regenerate()
 *
 * Skor dihasilkan oleh pipeline (Groq AI kalau API key aktif, fallback
 * heuristik lokal). Jalankan dari CLI:
 *   php database/seed_demo.php
 *
 * Aman dijalankan ulang: submission demo lama untuk user target dihapus
 * (cascade ke ai_reviews/defense_sessions/defense_questions) sebelum seed.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/agents/ReviewerAuditorAgent.php';
require_once __DIR__ . '/../includes/agents/DefenseAgent.php';
require_once __DIR__ . '/../includes/agents/ProfileGeneratorAgent.php';

$pdo = db();

// ---- Konfigurasi seed -------------------------------------------------
$students = [2 => 'rafi', 3 => 'sinta'];   // user_id => label
$taskIds  = [1, 2, 3];                     // id task yang akan dikerjakan

// ---- Konten submission per (student, task) ----------------------------
$content = [
    2 => [
        1 => '<?php
// auth.php — login dengan prepared statement + verifikasi password
require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);
    $password = $_POST["password"] ?? "";

    if ($email === false || $password === "") {
        $error = "Email tidak valid atau password kosong.";
    } else {
        $stmt = $pdo->prepare("SELECT id, nama, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password_hash"])) {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["id"];
            header("Location: dashboard.php");
            exit;
        }
        $error = "Email atau password salah.";
    }
}
',
        2 => '-- Optimasi laporan penjualan: hindari SELECT *, pakai JOIN berindex
CREATE INDEX idx_pesanan_tanggal ON pesanan(tanggal);
CREATE INDEX idx_detail_pesanan_pesanan ON detail_pesanan(pesanan_id);

SELECT p.id, p.tanggal, k.nama AS kasir,
       SUM(d.harga * d.qty) AS total
FROM pesanan p
JOIN kasir k ON k.id = p.kasir_id
JOIN detail_pesanan d ON d.pesanan_id = p.id
WHERE p.tanggal BETWEEN "2025-01-01" AND "2025-12-31"
GROUP BY p.id, p.tanggal, k.nama
ORDER BY p.tanggal DESC;
',
        3 => '<?php
// validator.php — komponen validasi form registrasi
class FormValidator {
    private $errors = [];

    public function validasiNama(string $nama): bool {
        if (strlen(trim($nama)) < 3) {
            $this->errors[] = "Nama minimal 3 karakter.";
            return false;
        }
        return true;
    }

    public function validasiEmail(string $email): bool {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Format email tidak valid.";
            return false;
        }
        return true;
    }

    public function validasiPassword(string $pass): bool {
        if (strlen($pass) < 8 || !preg_match("/[A-Za-z]/", $pass) || !preg_match("/[0-9]/", $pass)) {
            $this->errors[] = "Password minimal 8 karakter, mengandung huruf dan angka.";
            return false;
        }
        return true;
    }

    public function errors(): array { return $this->errors; }
    public function valid(): bool { return empty($this->errors); }
}
',
    ],
    3 => [
        1 => '<?php
// login.php — coba perbaiki query login
require "koneksi.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $pass = $_POST["password"];

    // cek user by email
    $sql = "SELECT * FROM users WHERE email = $email LIMIT 1";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if ($user && $pass === $user["password"]) {
        $_SESSION["user"] = $user;
        header("Location: dashboard.php");
    } else {
        $error = "Login gagal.";
    }
}
',
        2 => '<?php
// laporan penjualan.php — query lambat, dicoba optimasi sedikit
$rows = $pdo->query("SELECT * FROM pesanan WHERE tanggal BETWEEN \'2025-01-01\' AND \'2025-12-31\'")->fetchAll();

$laporan = [];
foreach ($rows as $r) {
    $total = $pdo->query("SELECT SUM(harga * qty) FROM detail_pesanan WHERE pesanan_id = {$r[\'id\']}")->fetchColumn();
    $laporan[] = $r + [\'total\' => $total];
}
',
        3 => '<?php
// validasi registrasi
$nama = $_POST["nama"];
$email = $_POST["email"];
$pass = $_POST["password"];

if (strlen($nama) < 3) {
    $error = "Nama terlalu pendek";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = "Email tidak valid";
} elseif (strlen($pass) < 6) {
    $error = "Password terlalu pendek";
}

// TODO: kirim error satu per satu biar user gak bingung
',
    ],
];

// ---- Jawaban simulasi per (student, task) --------------------------------
// Rafi: jawaban detail merujuk keputusan spesifik di kodenya sendiri.
// Sinta: jawaban lebih umum/pendek — Agent Defense menilai pemahaman nyata.
$answers = [
    2 => [
        1 => [
            "Saya ganti query string yang menggabungkan input langsung menjadi prepared statement: `$pdo->prepare(\"SELECT id, nama, password_hash FROM users WHERE email = ?\")` lalu `execute([$email])`. Nilai email dikirim sebagai parameter terpisah, jadi input pengguna tidak pernah di-parse sebagai bagian dari SQL.",
            "Password diverifikasi dengan password_verify() terhadap hash yang dibuat password_hash(). Password asli tidak pernah disimpan plain dan tidak pernah diinterpolasi ke query.",
            "Setelah login saya panggil session_regenerate_id(true) dan hanya menyimpan user_id di session — mencegah session fixation dan tidak membocorkan data lain.",
        ],
        2 => [
            "Akar masalahnya query memindai seluruh tabel tanpa index dan memakai SELECT * yang membawa kolom tidak terpakai, jadi I/O dan memori boros.",
            "Saya buat index di pesanan(tanggal) dan detail_pesanan(pesanan_id), lalu ganti pola query-per-baris dengan JOIN + GROUP BY sehingga agregasi total dikerjakan database dalam satu pass.",
            "Karena hanya kolom yang benar-benar dibutuhkan (p.id, p.tanggal, k.nama, SUM total) yang diproyeksikan dan filter tanggal mempersempit scan, beban database turun signifikan.",
        ],
        3 => [
            "Validasi wajib di server karena validasi client bisa dimatikan; saya pisahkan jadi komponen FormValidator agar bisa dipakai ulang dan diuji.",
            "Email dicek dengan filter_var(FILTER_VALIDATE_EMAIL), password dipaksa minimal 8 karakter dengan kombinasi huruf dan angka lewat regex.",
            "Semua pesan error dikumpulkan dalam array dan dirender bersama, jadi pengguna tahu semua perbaikan dalam satu kali submit.",
        ],
    ],
    3 => [
        1 => [
            "Saya coba perbaiki query login supaya tidak lagi menyisipkan input langsung.",
            "Password dicek dengan password_verify terhadap hash yang tersimpan.",
            "Saya membatasi hasil query dengan LIMIT 1 biar tidak kembar.",
        ],
        2 => [
            "Laporannya lambat karena mengambil semua data dulu baru dihitung.",
            "Saya coba pakai subquery untuk menghitung total tiap pesanan.",
            "Filter tanggal sudah membatasi pesanan yang diambil.",
        ],
        3 => [
            "Validasi penting supaya data yang masuk tidak sembarangan.",
            "Saya cek email dengan filter_var dan password minimal 6 karakter.",
            "Error disampaikan satu per satu lewat variabel.",
        ],
    ],
];

// ---- Buka/muat task + kategori ------------------------------------------
$taskStmt = $pdo->prepare('SELECT t.*, c.name AS category_name, c.submission_type, c.rubric_criteria FROM tasks t JOIN task_categories c ON c.id = t.category_id WHERE t.id = ?');
$tasks = [];
foreach ($taskIds as $id) {
    $taskStmt->execute([$id]);
    $t = $taskStmt->fetch();
    if ($t) $tasks[$id] = $t;
}

// ---- Bersihkan data demo lama (cascade) ---------------------------------
$pdo->prepare('DELETE FROM submissions WHERE user_id = ?')->execute([array_key_first($students)]);
$pdo->prepare('DELETE FROM submissions WHERE user_id = ?')->execute([array_key_last($students)]);

$reviewer   = new ReviewerAuditorAgent();
$defense    = new DefenseAgent();
$generator  = new ProfileGeneratorAgent();
$aiClient   = new AIClient();

// ---- Pipeline seed -------------------------------------------------------
foreach ($students as $uid => $label) {
    echo "=== $label (#$uid) ===\n";
    foreach ($tasks as $taskId => $task) {
        $code = $content[$uid][$taskId] ?? null;
        if ($code === null) { echo "  skip task #$taskId (no sample content)\n"; continue; }

        // 1. Insert submission
        $pdo->prepare('INSERT INTO submissions (task_id, user_id, language, code_content, status) VALUES (?,?,?,?,\'submitted\')')
            ->execute([$taskId, $uid, $task['submission_type'] ?: 'code', $code]);
        $subId = (int) $pdo->lastInsertId();
        $rubric = task_rubric($task);

        // 2. Review oleh Agent Reviewer & Auditor
        $review = $reviewer->review($code, $task['case_brief'], $rubric, $task['submission_type'] ?: 'code');
        $pdo->prepare('INSERT INTO ai_reviews (submission_id, clean_code_score, security_score, efficiency_score, overall_score, summary, findings_json) VALUES (?,?,?,?,?,?,?)')
            ->execute([$subId, $review['clean_code_score'], $review['security_score'], $review['efficiency_score'], $review['overall_score'], $review['summary'], json_encode($review['findings'], JSON_UNESCAPED_UNICODE)]);
        $pdo->prepare("UPDATE submissions SET status='reviewed' WHERE id = ?")->execute([$subId]);

        // 3. Agent Defense: generate pertanyaan + sesi pending
        $dq = $defense->generateQuestions($code, $task['case_brief'], $review['findings']);
        $pdo->prepare('INSERT INTO defense_sessions (submission_id, status, ai_assisted) VALUES (?, \'pending\', ?)')
            ->execute([$subId, $dq['ai_assisted'] ? 1 : 0]);
        $sessionId = (int) $pdo->lastInsertId();
        $qIns = $pdo->prepare('INSERT INTO defense_questions (session_id, order_index, question) VALUES (?,?,?)');
        foreach ($dq['questions'] as $i => $q) {
            $qIns->execute([$sessionId, $i, $q]);
        }

        // 4. Simulasi jawab + evaluasi oleh Agent Defense
        //    Rafi: jawaban ditulis oleh AI sesuai pertanyaan spesifiknya (siswa kuat).
        //    Sinta: jawaban canned yang pendek/umum (siswa lemah).
        $questions = $pdo->prepare('SELECT * FROM defense_questions WHERE session_id = ? ORDER BY order_index ASC');
        $questions->execute([$sessionId]);
        $qs = $questions->fetchAll();
        $generated = null;
        if ($uid === 2 && $aiClient->isAvailable()) {
            $qList = implode("\n", array_map(fn($i, $q) => ($i + 1) . '. ' . $q['question'], array_keys($qs), $qs));
            $gen = $aiClient->completeJson(
                'Kamu adalah siswa SMK jurusan RPL yang baru saja menyelesaikan studi kasus dan memahami betul kode yang kamu tulis. Jawab setiap pertanyaan dengan 2-3 kalimat, bahasa Indonesia, spesifik merujuk keputusan di kode kamu. Jangan mengulang jawaban yang sama untuk pertanyaan berbeda.',
                [['role' => 'user', 'content' => "Kode yang kamu submit:\n```\n{$code}\n```\n\nJawab SEMUA pertanyaan di bawah, balas HANYA array JSON string: [\"jawaban 1\",\"jawaban 2\",...]\n\nPertanyaan:\n{$qList}"]],
                1000
            );
            if (is_array($gen)) {
                $generated = array_values(array_map('trim', $gen));
            }
        }
        $theme = $answers[$uid][$taskId] ?? [];
        $qa = [];
        foreach ($qs as $i => $q) {
            $ans = isset($generated[$i]) && $generated[$i] !== ''
                ? (string) $generated[$i]
                : ($theme[$i % count($theme)] ?? 'Saya menerapkan pendekatan yang dijelaskan pada studi kasus.');
            $qa[] = ['id' => $q['id'], 'question' => $q['question'], 'answer' => $ans];
            $pdo->prepare('UPDATE defense_questions SET answer = ? WHERE id = ?')->execute([$ans, $q['id']]);
        }
        $result = $defense->evaluateAnswers($code, $qa);
        $updScore = $pdo->prepare('UPDATE defense_questions SET answer_score = ?, answer_feedback = ? WHERE id = ?');
        foreach ($result['per_question'] as $i => $pq) {
            if (isset($qa[$i])) {
                $updScore->execute([(int) ($pq['score'] ?? 0), $pq['feedback'] ?? '', $qa[$i]['id']]);
            }
        }
        $pdo->prepare('UPDATE defense_sessions SET status=\'evaluated\', comprehension_score=?, feedback=?, ai_assisted=?, evaluated_at=NOW() WHERE id = ?')
            ->execute([$result['comprehension_score'], $result['feedback'], $result['ai_assisted'] ? 1 : 0, $sessionId]);

        // 5. Regenerate profil + tracks
        $generator->regenerate($uid);

        $mode = $review['ai_assisted'] ? 'Groq' : 'heuristik';
        echo "  task #$taskId \"{$task['title']}\" -> skor {$review['overall_score']}/100 (review: {$mode}, defense: {$result['comprehension_score']}/100)\n";
    }
}

echo "\n=== Selesai. Ringkasan:\n";
echo "submissions   : " . $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn() . "\n";
echo "ai_reviews    : " . $pdo->query("SELECT COUNT(*) FROM ai_reviews")->fetchColumn() . "\n";
echo "defense_sessions (evaluated): " . $pdo->query("SELECT COUNT(*) FROM defense_sessions WHERE status='evaluated'")->fetchColumn() . "\n";
echo "profile_tracks: " . $pdo->query("SELECT COUNT(*) FROM skill_profile_tracks")->fetchColumn() . "\n";
foreach ($pdo->query("SELECT spt.user_id, c.name, spt.overall_score, spt.comprehension_avg FROM skill_profile_tracks spt JOIN task_categories c ON c.id = spt.category_id ORDER BY spt.user_id, c.name") as $tr) {
    echo "  user #{$tr['user_id']} {$tr['name']}: {$tr['overall_score']}/100 (comprehension {$tr['comprehension_avg']})\n";
}
