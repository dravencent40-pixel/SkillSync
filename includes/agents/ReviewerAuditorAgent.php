<?php
require_once __DIR__ . '/AIClient.php';

/**
 * SkillSync AI — Agent Reviewer & Auditor
 *
 * Berperan sebagai "Senior Tech Lead" yang mengaudit kode kiriman siswa:
 * - clean_code_score  : penamaan, komentar, panjang baris/fungsi, konsistensi
 * - security_score    : pola rawan SQL Injection, XSS, secret hardcoded, eval()
 * - efficiency_score  : query di dalam loop (N+1), perulangan tidak perlu
 *
 * Mode Hybrid: sekumpulan pemeriksaan keamanan deterministik (regex, presisi
 * tinggi) SELALU dijalankan, baik saat Claude tersedia maupun tidak. Ini
 * membuat temuan kritikal (SQLi, XSS, secret hardcoded) tidak bergantung
 * 100% pada penilaian LLM yang sifatnya probabilistik — setiap temuan diberi
 * label sumber ('static-verified' vs 'ai-judged') supaya siswa dan mitra
 * tahu persis mana yang machine-verified dan mana yang penilaian kualitatif
 * AI. Ini yang membuat skor bisa dipertanggungjawabkan, bukan black-box.
 */
class ReviewerAuditorAgent
{
    private AIClient $ai;

    public function __construct()
    {
        $this->ai = new AIClient();
    }

    /**
     * @return array{clean_code_score:int,security_score:int,efficiency_score:int,
     *               overall_score:int,summary:string,findings:array,ai_assisted:bool}
     */
    public function review(string $code, string $taskBrief): array
    {
        $staticFindings = $this->staticVerifiedChecks($code);

        if ($this->ai->isAvailable()) {
            $result = $this->reviewWithAI($code, $taskBrief, $staticFindings);
            if ($result !== null) {
                return $result;
            }
        }

        return $this->auditStatic($code, $staticFindings);
    }

    private function reviewWithAI(string $code, string $taskBrief, array $staticFindings): ?array
    {
        $staticNote = empty($staticFindings)
            ? 'Pemeriksaan statis deterministik tidak menemukan pola berbahaya yang jelas.'
            : 'Pemeriksaan statis deterministik SUDAH menemukan hal berikut secara pasti (jangan diulang di findings-mu, cukup pertimbangkan dalam skor): '
              . implode('; ', array_map(fn($f) => $f['title'], $staticFindings));

        $system = "Kamu adalah SkillSync AI Reviewer & Auditor — Senior Tech Lead yang mengaudit kode siswa SMK "
                . "untuk kesiapan magang di industri. Nilai berdasarkan: clean code (penamaan, struktur, komentar), "
                . "keamanan (SQL Injection, XSS, secret hardcoded, validasi input), dan efisiensi (kompleksitas, "
                . "query N+1, redundansi). Bersikap membangun dan spesifik, sebutkan nomor baris bila relevan. "
                . "{$staticNote} "
                . "Balas dalam format JSON: {\"clean_code_score\":0-100,\"security_score\":0-100,"
                . "\"efficiency_score\":0-100,\"summary\":\"ringkasan 2-3 kalimat berbahasa Indonesia\","
                . "\"findings\":[{\"severity\":\"info|warning|critical\",\"title\":\"...\",\"detail\":\"...\"}]}";

        $user = "Studi kasus:\n{$taskBrief}\n\nKode kiriman siswa:\n```\n{$code}\n```";

        $result = $this->ai->completeJson($system, [['role' => 'user', 'content' => $user]], 1500);
        if ($result === null || !isset($result['clean_code_score'])) {
            return null;
        }

        $clean = (int) $result['clean_code_score'];
        $sec   = (int) $result['security_score'];
        $eff   = (int) $result['efficiency_score'];

        // Setiap temuan AI ditandai sumbernya secara eksplisit, digabung dengan
        // temuan statis yang sudah pasti (deterministik) — bukan hasil "karangan" LLM.
        $aiFindings = array_map(function ($f) {
            $f['source'] = 'ai-judged';
            return $f;
        }, $result['findings'] ?? []);

        $merged = array_merge($staticFindings, $aiFindings);
        if (empty($merged)) {
            $merged[] = ['severity' => 'info', 'title' => 'Tidak ada masalah signifikan terdeteksi',
                'detail' => 'Audit AI dan pemeriksaan statis sama-sama tidak menemukan isu berarti.', 'source' => 'static-verified'];
        }

        // Skor keamanan tidak boleh lebih tinggi dari yang diizinkan temuan statis kritikal —
        // mencegah LLM "melunakkan" skor padahal ada bukti pasti SQLi/XSS/secret bocor.
        $hardCap = $this->securityHardCap($staticFindings);
        if ($hardCap !== null) {
            $sec = min($sec, $hardCap);
        }

        return [
            'clean_code_score' => $clean,
            'security_score'   => max(0, min(100, $sec)),
            'efficiency_score' => $eff,
            'overall_score'    => $this->weightedOverall($clean, max(0, min(100, $sec)), $eff),
            'summary'          => $result['summary'] ?? 'Ulasan tersedia pada daftar temuan.',
            'findings'         => $merged,
            'ai_assisted'      => true,
        ];
    }

    /**
     * Mode heuristik offline — dipakai murni sebagai fallback saat AI tidak
     * tersedia. Sekarang berbagi mesin pemeriksaan yang sama dengan
     * staticVerifiedChecks() supaya hasilnya konsisten dengan mode hybrid.
     */
    private function auditStatic(string $code, array $staticFindings): array
    {
        $findings = $staticFindings;
        $lines = explode("\n", $code);
        $totalLines = max(count($lines), 1);

        $securityScore = 100 - array_sum(array_column($staticFindings, 'penalty'));
        $securityScore = max(0, min(100, $securityScore));

        // --- Efficiency checks -------------------------------------------------
        $efficiencyScore = 100;
        if (preg_match('/for(each)?\s*\([^)]*\)\s*\{[^}]*(query|find|select)\s*\(/is', $code)) {
            $efficiencyScore -= 35;
            $findings[] = ['severity' => 'warning', 'title' => 'Kemungkinan query N+1',
                'detail' => 'Ditemukan pemanggilan query di dalam loop. Pertimbangkan JOIN atau satu query dengan WHERE IN (...) di luar loop.', 'source' => 'static-verified'];
        }
        if (preg_match_all('/for\s*\(.*for\s*\(.*for\s*\(/is', $code)) {
            $efficiencyScore -= 15;
            $findings[] = ['severity' => 'info', 'title' => 'Nested loop dalam (kompleksitas tinggi)',
                'detail' => 'Terdapat perulangan bersarang lebih dari dua tingkat, periksa apakah bisa disederhanakan.', 'source' => 'static-verified'];
        }
        $efficiencyScore = max(0, min(100, $efficiencyScore));

        // --- Clean code checks ----------------------------------------------
        $cleanScore = 100;
        $longLines = 0;
        $commentLines = 0;
        foreach ($lines as $line) {
            if (strlen($line) > 120) $longLines++;
            if (preg_match('/^\s*(\/\/|#|\*|\/\*)/', $line)) $commentLines++;
        }
        if ($longLines > 0) {
            $penalty = min(20, $longLines * 3);
            $cleanScore -= $penalty;
            $findings[] = ['severity' => 'info', 'title' => 'Baris terlalu panjang',
                'detail' => "$longLines baris melebihi 120 karakter. Pecah menjadi beberapa baris agar mudah dibaca.", 'source' => 'static-verified'];
        }
        $commentRatio = $commentLines / $totalLines;
        if ($commentRatio < 0.03 && $totalLines > 15) {
            $cleanScore -= 15;
            $findings[] = ['severity' => 'info', 'title' => 'Minim komentar/dokumentasi',
                'detail' => 'Tambahkan komentar singkat pada bagian logika yang kompleks untuk memudahkan maintenance.', 'source' => 'static-verified'];
        }
        if (preg_match('/function\s+[a-z]/', $code) && preg_match('/function\s+[A-Z]/', $code)) {
            $cleanScore -= 10;
            $findings[] = ['severity' => 'info', 'title' => 'Konvensi penamaan tidak konsisten',
                'detail' => 'Campuran camelCase dan PascalCase pada nama fungsi ditemukan. Pilih satu konvensi dan konsisten.', 'source' => 'static-verified'];
        }
        if (preg_match('/\bTODO\b|\bFIXME\b/i', $code)) {
            $cleanScore -= 5;
            $findings[] = ['severity' => 'info', 'title' => 'Masih ada penanda TODO/FIXME',
                'detail' => 'Selesaikan bagian yang masih ditandai TODO sebelum submit final.', 'source' => 'static-verified'];
        }
        $cleanScore = max(0, min(100, $cleanScore));

        if (empty($findings)) {
            $findings[] = ['severity' => 'info', 'title' => 'Tidak ada masalah signifikan terdeteksi',
                'detail' => 'Kode cukup rapi pada pemeriksaan pola dasar. Tetap perhatikan edge case dan penanganan error.', 'source' => 'static-verified'];
        }

        $overall = $this->weightedOverall($cleanScore, $securityScore, $efficiencyScore);
        $summary = "Audit otomatis (mode heuristik lokal — belum tersambung ke Claude API) menilai kode ini dengan skor keseluruhan {$overall}/100. "
                 . "Fokus perbaikan utama: " . $this->topWeakArea($cleanScore, $securityScore, $efficiencyScore) . ".";

        return [
            'clean_code_score' => $cleanScore,
            'security_score'   => $securityScore,
            'efficiency_score' => $efficiencyScore,
            'overall_score'    => $overall,
            'summary'          => $summary,
            'findings'         => $findings,
            'ai_assisted'      => false,
        ];
    }

    /**
     * Pemeriksaan keamanan deterministik (presisi tinggi, dipakai di kedua mode).
     * Setiap temuan membawa 'penalty' (dipakai auditStatic()) dan 'source'
     * bertanda 'static-verified' supaya jelas ini bukan opini AI.
     *
     * SQLi ditemukan dengan dua pola:
     *  (a) $_GET/POST/REQUEST langsung digabung/diinterpolasi ke string SQL, dan
     *  (b) pola paling umum di kode siswa: variabel biasa (mis. $username) yang
     *      DIINTERPOLASI ke dalam string yang mengandung keyword SQL — tanpa
     *      prepare()/bindParam() di dekatnya. Pola (b) inilah yang terlewat di
     *      versi sebelumnya karena hanya mengecek $_POST/$_GET secara langsung,
     *      padahal pola paling lazim di kode nyata adalah lewat variabel antara.
     */
    private function staticVerifiedChecks(string $code): array
    {
        $findings = [];
        $hasPreparedStatement = (bool) preg_match('/->prepare\s*\(|mysqli_prepare\s*\(|bindParam|bindValue/i', $code);

        $directUserInputInQuery = preg_match('/\$_(GET|POST|REQUEST)\s*\[[^\]]+\]\s*\.?\s*["\']?\s*\.?\s*(SELECT|INSERT|UPDATE|DELETE)/i', $code)
            || preg_match('/["\']\s*\.\s*\$_(GET|POST|REQUEST)/i', $code);

        // Variabel biasa yang diinterpolasi ke dalam string ber-keyword SQL, mis:
        // "SELECT * FROM users WHERE username = '$username'"
        $variableInterpolatedInSqlString = preg_match(
            '/["\'][^"\']*\b(SELECT|INSERT|UPDATE|DELETE)\b[^"\']*\$[a-zA-Z_][a-zA-Z0-9_]*[^"\']*["\']/is',
            $code
        );

        if (!$hasPreparedStatement && ($directUserInputInQuery || $variableInterpolatedInSqlString)) {
            $findings[] = [
                'severity' => 'critical',
                'title'    => 'Potensi SQL Injection',
                'detail'   => 'Query SQL tampak dibangun dengan menggabungkan/menginterpolasi variabel langsung ke dalam string, tanpa prepared statement. Gunakan PDO::prepare() dengan parameter binding (mis. $stmt = $pdo->prepare("... WHERE username = ?"); $stmt->execute([$username]);).',
                'source'   => 'static-verified',
                'penalty'  => 40,
            ];
        }

        if (preg_match('/\b(eval|exec|system|passthru|shell_exec)\s*\(/i', $code)) {
            $findings[] = [
                'severity' => 'critical',
                'title'    => 'Fungsi berbahaya terdeteksi',
                'detail'   => 'Penggunaan eval()/exec()/system() sangat rawan disalahgunakan untuk Remote Code Execution.',
                'source'   => 'static-verified',
                'penalty'  => 30,
            ];
        }

        if (preg_match('/echo\s+\$_(GET|POST|REQUEST)/i', $code)
            || preg_match('/echo\s+\$[a-zA-Z_][a-zA-Z0-9_]*\s*;/', $code) && preg_match('/\$_(GET|POST|REQUEST)\s*\[[^\]]+\]\s*;\s*(\/\/.*)?\n.*echo/i', $code)) {
            if (!preg_match('/htmlspecialchars|htmlentities/i', $code)) {
                $findings[] = [
                    'severity' => 'warning',
                    'title'    => 'Potensi Cross-Site Scripting (XSS)',
                    'detail'   => 'Output input pengguna langsung tanpa htmlspecialchars() berisiko XSS.',
                    'source'   => 'static-verified',
                    'penalty'  => 20,
                ];
            }
        }

        if (preg_match('/(password|api_key|secret)\s*=\s*["\'][^"\']{4,}["\']/i', $code)) {
            $findings[] = [
                'severity' => 'warning',
                'title'    => 'Kredensial ter-hardcode',
                'detail'   => 'Ditemukan string yang menyerupai password/API key tertulis langsung di kode. Pindahkan ke environment variable.',
                'source'   => 'static-verified',
                'penalty'  => 15,
            ];
        }

        return $findings;
    }

    /** Batas atas skor keamanan bila ada temuan kritikal yang sudah pasti (deterministik). */
    private function securityHardCap(array $staticFindings): ?int
    {
        $penalty = array_sum(array_column($staticFindings, 'penalty'));
        return $penalty > 0 ? max(0, 100 - $penalty) : null;
    }

    private function weightedOverall(int $clean, int $sec, int $eff): int
    {
        // Keamanan diberi bobot lebih besar karena kritikal untuk kode produksi.
        return (int) round(($clean * 0.35) + ($sec * 0.4) + ($eff * 0.25));
    }

    private function topWeakArea(int $clean, int $sec, int $eff): string
    {
        $areas = ['Clean Code' => $clean, 'Keamanan' => $sec, 'Efisiensi' => $eff];
        asort($areas);
        return array_key_first($areas);
    }
}
