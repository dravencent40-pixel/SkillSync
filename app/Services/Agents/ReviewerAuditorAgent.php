<?php

namespace App\Services\Agents;

/**
 * SkillSync — Agent Reviewer & Auditor
 *
 * Berperan sebagai "Senior Reviewer" lintas-divisi — bukan cuma kode. Kriteria
 * penilaian datang dari rubric_criteria milik task_categories (3 kriteria per
 * divisi: Programming, UI/UX Design, Jaringan & Infrastruktur, dst), jadi
 * agent yang sama bisa menilai submission kode, desain (gambar/link Figma),
 * maupun dokumentasi jaringan — dengan pertanyaan penilaian yang relevan
 * masing-masing, bukan "SQL Injection" dipaksakan ke semua divisi.
 *
 * Untuk konsistensi dengan skema database (kolom clean_code_score/
 * security_score/efficiency_score di ai_reviews), 3 skor itu dipakai sebagai
 * SLOT GENERIK untuk rubric_criteria[0/1/2] — label yang tampil ke siswa
 * mengikuti rubric kategori masing-masing (lihat task_rubric() di
 * functions.php), bukan selalu "Clean Code/Keamanan/Efisiensi" secara harfiah.
 *
 * Mode Hybrid KHUSUS submission_type='code': sekumpulan pemeriksaan keamanan
 * deterministik (regex, presisi tinggi) SELALU dijalankan di samping AI —
 * temuan diberi label sumber ('static-verified' vs 'ai-judged'). Untuk divisi
 * non-kode (desain/jaringan) belum ada pemeriksaan deterministik yang setara,
 * jadi penilaian sepenuhnya rubric+AI (atau heuristik konservatif).
 */
class ReviewerAuditorAgent
{
    private AIClient $ai;

    public function __construct()
    {
        $this->ai = new AIClient();
    }

    /**
     * @param string      $content        Kode, ATAU deskripsi/dokumentasi untuk submission non-kode
     * @param string      $taskBrief      Deskripsi studi kasus
     * @param array       $rubric         3 kriteria [{key,label,description}, ...] dari task_rubric()
     * @param string      $submissionType 'code'|'design'|'network'|'general'
     * @param string|null $externalLink   Link eksternal (mis. Figma) bila ada
     * @param string|null $fileName       Nama file yang diunggah siswa bila ada — HANYA nama file yang
     *                                    dikirim ke AI (bukan isi gambar; lihat catatan di reviewWithAI())
     * @return array{clean_code_score:int,security_score:int,efficiency_score:int,
     *               overall_score:int,summary:string,findings:array,ai_assisted:bool}
     */
    public function review(string $content, string $taskBrief, array $rubric, string $submissionType = 'code', ?string $externalLink = null, ?string $fileName = null): array
    {
        $isCode = $submissionType === 'code';
        $staticFindings = $isCode ? $this->staticVerifiedChecks($content) : [];

        if ($this->ai->isAvailable()) {
            $result = $this->reviewWithAI($content, $taskBrief, $staticFindings, $rubric, $submissionType, $externalLink, $fileName);
            if ($result !== null) {
                return $result;
            }
        }

        return $isCode
            ? $this->auditStatic($content, $staticFindings)
            : $this->auditGenericHeuristic($content, $rubric, $submissionType, $externalLink, $fileName);
    }

    private function reviewWithAI(string $content, string $taskBrief, array $staticFindings, array $rubric, string $submissionType, ?string $externalLink, ?string $fileName): ?array
    {
        [$c1, $c2, $c3] = [$rubric[0], $rubric[1], $rubric[2]];
        $staticNote = '';
        if ($submissionType === 'code') {
            $staticNote = empty($staticFindings)
                ? 'Pemeriksaan statis deterministik tidak menemukan pola berbahaya yang jelas.'
                : 'Pemeriksaan statis deterministik SUDAH menemukan hal berikut secara pasti (jangan diulang di findings-mu, cukup pertimbangkan dalam skor): '
                  . implode('; ', array_map(fn($f) => $f['title'], $staticFindings));
        }

        $roleContext = [
            'code'    => 'Senior Tech Lead yang mengaudit kode siswa SMK untuk kesiapan magang di industri',
            'design'  => 'Senior Product Designer yang mereview hasil desain UI/UX siswa SMK untuk kesiapan magang di industri',
            'network' => 'Senior Network Engineer yang mereview rancangan/dokumentasi jaringan siswa SMK untuk kesiapan magang di industri',
            'general' => 'Senior Reviewer lintas-divisi yang menilai hasil kerja siswa SMK untuk kesiapan magang di industri',
        ][$submissionType] ?? 'Senior Reviewer';

        $system = "Kamu adalah SkillSync Reviewer & Auditor — {$roleContext}. "
                . "Nilai berdasarkan TEPAT 3 kriteria berikut (jangan menilai di luar ini): "
                . "(1) {$c1['label']} — {$c1['description']}; "
                . "(2) {$c2['label']} — {$c2['description']}; "
                . "(3) {$c3['label']} — {$c3['description']}. "
                . "Bersikap membangun dan spesifik, rujuk bagian konkret dari hasil kerja siswa. {$staticNote} "
                . "Balas dalam format JSON: {\"criterion1_score\":0-100,\"criterion2_score\":0-100,"
                . "\"criterion3_score\":0-100,\"summary\":\"ringkasan 2-3 kalimat berbahasa Indonesia\","
                . "\"findings\":[{\"severity\":\"info|warning|critical\",\"title\":\"...\",\"detail\":\"...\"}]}";

        $extra = '';
        if ($externalLink) $extra .= "\nLink eksternal yang disertakan siswa: {$externalLink}";
        if ($fileName) $extra .= "\nSiswa juga melampirkan file: {$fileName} (nama file saja — kamu tidak bisa melihat isinya, nilai berdasarkan deskripsi siswa dan konteks yang tersedia).";

        $user = "Studi kasus:\n{$taskBrief}\n\nHasil kerja/penjelasan siswa:\n```\n{$content}\n```{$extra}";

        $result = $this->ai->completeJson($system, [['role' => 'user', 'content' => $user]], 1500);
        if ($result === null || !isset($result['criterion1_score'])) {
            return null;
        }

        $s1 = (int) $result['criterion1_score'];
        $s2 = (int) $result['criterion2_score'];
        $s3 = (int) $result['criterion3_score'];

        // Setiap temuan AI ditandai sumbernya secara eksplisit, digabung dengan
        // temuan statis yang sudah pasti (deterministik, khusus submission kode).
        $aiFindings = array_map(function ($f) {
            $f['source'] = 'ai-judged';
            return $f;
        }, $result['findings'] ?? []);

        $merged = array_merge($staticFindings, $aiFindings);
        if (empty($merged)) {
            $merged[] = ['severity' => 'info', 'title' => 'Tidak ada masalah signifikan terdeteksi',
                'detail' => 'Audit AI tidak menemukan isu berarti pada ketiga kriteria yang dinilai.', 'source' => 'ai-judged'];
        }

        // Skor keamanan (khusus kode) tidak boleh lebih tinggi dari yang diizinkan
        // temuan statis kritikal — mencegah LLM "melunakkan" skor padahal ada bukti
        // pasti SQLi/XSS/secret bocor. Hanya berlaku untuk submission_type='code'.
        if ($submissionType === 'code') {
            $hardCap = $this->securityHardCap($staticFindings);
            if ($hardCap !== null) {
                $s2 = min($s2, $hardCap);
            }
        }

        return [
            'clean_code_score' => max(0, min(100, $s1)),
            'security_score'   => max(0, min(100, $s2)),
            'efficiency_score' => max(0, min(100, $s3)),
            'overall_score'    => $this->weightedOverall($s1, $s2, $s3),
            'summary'          => $result['summary'] ?? 'Ulasan tersedia pada daftar temuan.',
            'findings'         => $merged,
            'ai_assisted'      => true,
        ];
    }

    /**
     * Fallback heuristik untuk submission NON-KODE (desain/jaringan/umum) saat
     * AI tidak tersedia. Jauh lebih sederhana & konservatif dibanding auditStatic()
     * karena tidak ada pemeriksaan pola deterministik yang setara untuk divisi
     * ini — cuma menilai kelengkapan (ada link/file, panjang penjelasan) sebagai
     * proksi kasar, BUKAN kualitas substansi. Skor dipagari maksimum 70 dan
     * summary selalu menyebutkan keterbatasan ini secara eksplisit.
     */
    private function auditGenericHeuristic(string $content, array $rubric, string $submissionType, ?string $externalLink, ?string $fileName): array
    {
        $wordCount = str_word_count(strip_tags($content));
        $hasEvidence = $externalLink || $fileName;

        $completeness = match (true) {
            $wordCount < 15 => 25,
            $wordCount < 40 => 50,
            $wordCount < 100 => 70,
            default => 80,
        };
        if (!$hasEvidence && in_array($submissionType, ['design', 'network'], true)) {
            $completeness -= 15; // desain/jaringan idealnya selalu menyertakan bukti visual/dokumen
        }
        $completeness = max(0, min(70, $completeness)); // dipagari 70 — heuristik ini tidak menilai substansi

        $findings = [
            ['severity' => 'warning', 'title' => 'Dinilai dengan mode heuristik lokal',
                'detail' => 'Groq API tidak tersedia saat submission ini dinilai. Skor di bawah HANYA berdasarkan kelengkapan (panjang penjelasan, ada/tidaknya link/file) — BUKAN evaluasi substansi ' . $rubric[0]['label'] . '/' . $rubric[1]['label'] . '/' . $rubric[2]['label'] . ' yang sesungguhnya. Sambungkan GROQ_API_KEY lalu minta siswa submit ulang untuk penilaian yang valid.',
                'source' => 'static-verified'],
        ];
        if (!$hasEvidence) {
            $findings[] = ['severity' => 'info', 'title' => 'Belum ada link/file pendukung',
                'detail' => 'Sertakan link (mis. Figma) atau file (screenshot/dokumen) supaya reviewer punya bukti konkret untuk dinilai.', 'source' => 'static-verified'];
        }

        $overall = $this->weightedOverall($completeness, $completeness, $completeness);
        $summary = "Audit otomatis (mode heuristik lokal — belum tersambung ke Groq API) hanya bisa menilai kelengkapan "
                 . "submission ({$overall}/100), BELUM benar-benar mengevaluasi substansi {$rubric[0]['label']}, {$rubric[1]['label']}, "
                 . "maupun {$rubric[2]['label']}. Hubungkan Groq API untuk penilaian yang sesungguhnya.";

        return [
            'clean_code_score' => $completeness,
            'security_score'   => $completeness,
            'efficiency_score' => $completeness,
            'overall_score'    => $overall,
            'summary'          => $summary,
            'findings'         => $findings,
            'ai_assisted'      => false,
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
        $summary = "Audit otomatis (mode heuristik lokal — belum tersambung ke Groq API) menilai kode ini dengan skor keseluruhan {$overall}/100. "
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
