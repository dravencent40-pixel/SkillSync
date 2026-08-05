<?php
require_once __DIR__ . '/AIClient.php';

/**
 * SkillSync — Agent Profile Generator
 *
 * Mengagregasi seluruh ai_reviews milik seorang siswa menjadi satu
 * skill_profiles yang transparan dan siap direkomendasikan ke mitra industri.
 *
 * Skor (overall/clean/security/efficiency, badge) SELALU dihitung murni dari
 * data ai_reviews di database — bukan dikarang ulang oleh LLM — supaya bisa
 * dipertanggungjawabkan. AIClient (jika tersedia) hanya dipakai untuk menulis
 * SATU paragraf narasi kualitatif di atas angka-angka yang sudah pasti itu;
 * kalau AI tidak tersedia, dipakai narasi deterministik dari template.
 *
 * overall_score TIDAK murni dari kualitas kode — begitu siswa punya minimal
 * satu sesi Agent Defense yang sudah dievaluasi, overall_score dicampur
 * dengan comprehension_avg (bobot 70% kualitas kode : 30% pemahaman nyata).
 * Ini mencegah submission "kode rapi tapi tidak dipahami pemiliknya sendiri"
 * otomatis mendapat skor kompetensi tinggi — lihat DefenseAgent.
 *
 * Selain ringkasan lintas-divisi di atas, regenerate() juga menghitung
 * breakdown skor TERPISAH per kategori/divisi ke skill_profile_tracks
 * (lihat regenerateTracks()) — supaya dashboard bisa menampilkan skor
 * Programming vs UI/UX Design vs Jaringan secara terpisah, bukan
 * membaurkan kriteria yang beda makna jadi satu rata-rata.
 */
class ProfileGeneratorAgent
{
    private AIClient $ai;

    public function __construct()
    {
        $this->ai = new AIClient();
    }

    public function regenerate(int $userId): array
    {
        $pdo = db();

        $stmt = $pdo->prepare(
            'SELECT r.clean_code_score, r.security_score, r.efficiency_score, r.overall_score, t.title
             FROM ai_reviews r
             JOIN submissions s ON s.id = r.submission_id
             JOIN tasks t ON t.id = s.task_id
             WHERE s.user_id = ?
             ORDER BY r.reviewed_at ASC'
        );
        $stmt->execute([$userId]);
        $reviews = $stmt->fetchAll();

        // Skor sesi Agent Defense yang SUDAH dievaluasi (belum dijawab tidak dihitung).
        $defenseStmt = $pdo->prepare(
            'SELECT ds.comprehension_score
             FROM defense_sessions ds
             JOIN submissions s ON s.id = ds.submission_id
             WHERE s.user_id = ? AND ds.status = \'evaluated\''
        );
        $defenseStmt->execute([$userId]);
        $defenseScores = array_column($defenseStmt->fetchAll(), 'comprehension_score');
        $comprehensionAvg = $defenseScores ? (int) round(array_sum($defenseScores) / count($defenseScores)) : 0;

        $count = count($reviews);
        if ($count === 0) {
            $data = [
                'overall_score' => 0, 'clean_code_avg' => 0, 'security_avg' => 0,
                'efficiency_avg' => 0, 'comprehension_avg' => 0, 'tasks_completed' => 0, 'badge' => 'Pemula',
                'strengths' => null, 'weaknesses' => null, 'narrative' => null,
            ];
        } else {
            $avg = fn(string $key) => (int) round(array_sum(array_column($reviews, $key)) / $count);
            $clean = $avg('clean_code_score');
            $sec   = $avg('security_score');
            $eff   = $avg('efficiency_score');
            $codeOverall = $avg('overall_score');

            // Campur skor kode dengan skor pemahaman HANYA jika sudah ada minimal
            // satu sesi defense yang dievaluasi — kalau belum ada sama sekali,
            // jangan menjatuhkan skor siswa karena sesi defense belum sempat dikerjakan.
            $overall = $defenseScores
                ? (int) round(($codeOverall * 0.7) + ($comprehensionAvg * 0.3))
                : $codeOverall;

            $areas = ['Clean Code' => $clean, 'Keamanan' => $sec, 'Efisiensi' => $eff];
            if ($defenseScores) {
                $areas['Pemahaman Konsep'] = $comprehensionAvg;
            }
            arsort($areas);
            $strengths = array_key_first($areas);
            asort($areas);
            $weaknesses = array_key_first($areas);
            $badge = badge_from_score($overall);

            $data = [
                'overall_score'     => $overall,
                'clean_code_avg'    => $clean,
                'security_avg'      => $sec,
                'efficiency_avg'    => $eff,
                'comprehension_avg' => $comprehensionAvg,
                'tasks_completed'   => $count,
                'badge'             => $badge,
                'strengths'         => $strengths,
                'weaknesses'        => $weaknesses,
                'narrative'         => $this->buildNarrative($overall, $clean, $sec, $eff, $count, $badge, $strengths, $weaknesses, $reviews, $comprehensionAvg, (bool) $defenseScores),
            ];
        }

        $exists = $pdo->prepare('SELECT id FROM skill_profiles WHERE user_id = ?');
        $exists->execute([$userId]);

        if ($exists->fetch()) {
            $sql = 'UPDATE skill_profiles SET overall_score=?, clean_code_avg=?, security_avg=?, efficiency_avg=?,
                    comprehension_avg=?, tasks_completed=?, badge=?, strengths=?, weaknesses=?, narrative=? WHERE user_id=?';
            $pdo->prepare($sql)->execute([
                $data['overall_score'], $data['clean_code_avg'], $data['security_avg'], $data['efficiency_avg'],
                $data['comprehension_avg'], $data['tasks_completed'], $data['badge'], $data['strengths'], $data['weaknesses'], $data['narrative'], $userId,
            ]);
        } else {
            $sql = 'INSERT INTO skill_profiles (user_id, overall_score, clean_code_avg, security_avg, efficiency_avg,
                    comprehension_avg, tasks_completed, badge, strengths, weaknesses, narrative) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
            $pdo->prepare($sql)->execute([
                $userId, $data['overall_score'], $data['clean_code_avg'], $data['security_avg'], $data['efficiency_avg'],
                $data['comprehension_avg'], $data['tasks_completed'], $data['badge'], $data['strengths'], $data['weaknesses'], $data['narrative'],
            ]);
        }

        log_activity($userId, 'profile_regenerated', "Skor overall: {$data['overall_score']}/100 · {$data['tasks_completed']} studi kasus");

        $this->regenerateTracks($userId);

        return $data;
    }

    /**
     * Hitung & simpan rata-rata TERPISAH per kategori/divisi ke
     * skill_profile_tracks — supaya dashboard bisa menampilkan breakdown
     * yang jujur (mis. skor Programming vs skor UI/UX Design terpisah),
     * bukan satu angka yang membaurkan kriteria berbeda makna.
     */
    private function regenerateTracks(int $userId): void
    {
        $pdo = db();

        $stmt = $pdo->prepare(
            'SELECT t.category_id, r.clean_code_score AS c1, r.security_score AS c2, r.efficiency_score AS c3, r.overall_score
             FROM ai_reviews r
             JOIN submissions s ON s.id = r.submission_id
             JOIN tasks t ON t.id = s.task_id
             WHERE s.user_id = ?'
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $defStmt = $pdo->prepare(
            'SELECT t.category_id, ds.comprehension_score
             FROM defense_sessions ds
             JOIN submissions s ON s.id = ds.submission_id
             JOIN tasks t ON t.id = s.task_id
             WHERE s.user_id = ? AND ds.status = \'evaluated\''
        );
        $defStmt->execute([$userId]);
        $defRows = $defStmt->fetchAll();

        $byCategory = [];
        foreach ($rows as $r) {
            $byCategory[$r['category_id']]['reviews'][] = $r;
        }
        foreach ($defRows as $d) {
            $byCategory[$d['category_id']]['defense'][] = $d['comprehension_score'];
        }

        $upsert = $pdo->prepare(
            'INSERT INTO skill_profile_tracks (user_id, category_id, overall_score, criterion1_score, criterion2_score, criterion3_score, comprehension_avg, tasks_completed)
             VALUES (?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE overall_score=VALUES(overall_score), criterion1_score=VALUES(criterion1_score),
                criterion2_score=VALUES(criterion2_score), criterion3_score=VALUES(criterion3_score),
                comprehension_avg=VALUES(comprehension_avg), tasks_completed=VALUES(tasks_completed)'
        );

        foreach ($byCategory as $categoryId => $data) {
            $reviews = $data['reviews'] ?? [];
            $n = count($reviews);
            if ($n === 0) continue;

            $avg = fn(string $key) => (int) round(array_sum(array_column($reviews, $key)) / $n);
            $c1 = $avg('c1'); $c2 = $avg('c2'); $c3 = $avg('c3');
            $codeOverall = $avg('overall_score');

            $defenseScores = $data['defense'] ?? [];
            $compAvg = $defenseScores ? (int) round(array_sum($defenseScores) / count($defenseScores)) : 0;
            $overall = $defenseScores ? (int) round(($codeOverall * 0.7) + ($compAvg * 0.3)) : $codeOverall;

            $upsert->execute([$userId, $categoryId, $overall, $c1, $c2, $c3, $compAvg, $n]);
        }
    }

    private function buildNarrative(int $overall, int $clean, int $sec, int $eff, int $count, string $badge, string $strengths, string $weaknesses, array $reviews, int $comprehensionAvg, bool $hasDefense): string
    {
        $defenseNote = $hasDefense
            ? "Skor ini sudah memperhitungkan sesi pembelaan project (rata-rata pemahaman: {$comprehensionAvg}/100), bukan hanya kualitas kode."
            : "Siswa belum menyelesaikan sesi pembelaan project untuk memverifikasi pemahaman.";

        if ($this->ai->isAvailable()) {
            $recentTitles = implode(', ', array_slice(array_column($reviews, 'title'), -5));
            $system = "Kamu adalah SkillSync Profile Generator. Tulis SATU paragraf (3-4 kalimat) ringkasan "
                    . "kompetensi siswa untuk dibaca HR/tech lead mitra industri yang mempertimbangkan magang. "
                    . "Bahasa Indonesia, profesional, objektif — HANYA berdasarkan angka yang diberikan, jangan "
                    . "mengarang klaim di luar data. Sebutkan kekuatan utama dan satu area yang perlu berkembang.";
            $user = "Skor rata-rata dari {$count} studi kasus — Clean Code: {$clean}, Keamanan: {$sec}, "
                  . "Efisiensi: {$eff}, Overall: {$overall} (predikat: {$badge}). Kekuatan: {$strengths}. "
                  . "Area berkembang: {$weaknesses}. Studi kasus yang dikerjakan: {$recentTitles}. {$defenseNote}";
            $result = $this->ai->complete($system, [['role' => 'user', 'content' => $user]], 300);
            if ($result !== null) {
                return trim($result) . ' ' . $defenseNote;
            }
        }

        // Fallback deterministik — tetap informatif tanpa API key.
        return "Berdasarkan {$count} studi kasus yang diselesaikan, siswa ini meraih skor keseluruhan {$overall}/100 "
             . "(predikat {$badge}). Kekuatan utama ada pada aspek {$strengths}, sementara aspek {$weaknesses} masih "
             . "menjadi area yang perlu ditingkatkan sebelum benar-benar siap menangani proyek produksi secara mandiri. {$defenseNote}";
    }
}
