<?php
require_once __DIR__ . '/AIClient.php';

/**
 * SkillSync AI — Agent Profile Generator
 *
 * Mengagregasi seluruh ai_reviews milik seorang siswa menjadi satu
 * skill_profiles yang transparan dan siap direkomendasikan ke mitra industri.
 *
 * Skor (overall/clean/security/efficiency, badge) SELALU dihitung murni dari
 * data ai_reviews di database — bukan dikarang ulang oleh LLM — supaya bisa
 * dipertanggungjawabkan. AIClient (jika tersedia) hanya dipakai untuk menulis
 * SATU paragraf narasi kualitatif di atas angka-angka yang sudah pasti itu;
 * kalau AI tidak tersedia, dipakai narasi deterministik dari template.
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

        $count = count($reviews);
        if ($count === 0) {
            $data = [
                'overall_score' => 0, 'clean_code_avg' => 0, 'security_avg' => 0,
                'efficiency_avg' => 0, 'tasks_completed' => 0, 'badge' => 'Pemula',
                'strengths' => null, 'weaknesses' => null, 'narrative' => null,
            ];
        } else {
            $avg = fn(string $key) => (int) round(array_sum(array_column($reviews, $key)) / $count);
            $clean = $avg('clean_code_score');
            $sec   = $avg('security_score');
            $eff   = $avg('efficiency_score');
            $overall = $avg('overall_score');

            $areas = ['Clean Code' => $clean, 'Keamanan' => $sec, 'Efisiensi' => $eff];
            arsort($areas);
            $strengths = array_key_first($areas);
            asort($areas);
            $weaknesses = array_key_first($areas);
            $badge = badge_from_score($overall);

            $data = [
                'overall_score'   => $overall,
                'clean_code_avg'  => $clean,
                'security_avg'    => $sec,
                'efficiency_avg'  => $eff,
                'tasks_completed' => $count,
                'badge'           => $badge,
                'strengths'       => $strengths,
                'weaknesses'      => $weaknesses,
                'narrative'       => $this->buildNarrative($overall, $clean, $sec, $eff, $count, $badge, $strengths, $weaknesses, $reviews),
            ];
        }

        $exists = $pdo->prepare('SELECT id FROM skill_profiles WHERE user_id = ?');
        $exists->execute([$userId]);

        if ($exists->fetch()) {
            $sql = 'UPDATE skill_profiles SET overall_score=?, clean_code_avg=?, security_avg=?, efficiency_avg=?,
                    tasks_completed=?, badge=?, strengths=?, weaknesses=?, narrative=? WHERE user_id=?';
            $pdo->prepare($sql)->execute([
                $data['overall_score'], $data['clean_code_avg'], $data['security_avg'], $data['efficiency_avg'],
                $data['tasks_completed'], $data['badge'], $data['strengths'], $data['weaknesses'], $data['narrative'], $userId,
            ]);
        } else {
            $sql = 'INSERT INTO skill_profiles (user_id, overall_score, clean_code_avg, security_avg, efficiency_avg,
                    tasks_completed, badge, strengths, weaknesses, narrative) VALUES (?,?,?,?,?,?,?,?,?,?)';
            $pdo->prepare($sql)->execute([
                $userId, $data['overall_score'], $data['clean_code_avg'], $data['security_avg'], $data['efficiency_avg'],
                $data['tasks_completed'], $data['badge'], $data['strengths'], $data['weaknesses'], $data['narrative'],
            ]);
        }

        log_activity($userId, 'profile_regenerated', "Skor overall: {$data['overall_score']}/100 · {$data['tasks_completed']} studi kasus");

        return $data;
    }

    private function buildNarrative(int $overall, int $clean, int $sec, int $eff, int $count, string $badge, string $strengths, string $weaknesses, array $reviews): string
    {
        if ($this->ai->isAvailable()) {
            $recentTitles = implode(', ', array_slice(array_column($reviews, 'title'), -5));
            $system = "Kamu adalah SkillSync AI Profile Generator. Tulis SATU paragraf (3-4 kalimat) ringkasan "
                    . "kompetensi siswa untuk dibaca HR/tech lead mitra industri yang mempertimbangkan magang. "
                    . "Bahasa Indonesia, profesional, objektif — HANYA berdasarkan angka yang diberikan, jangan "
                    . "mengarang klaim di luar data. Sebutkan kekuatan utama dan satu area yang perlu berkembang.";
            $user = "Skor rata-rata dari {$count} studi kasus — Clean Code: {$clean}, Keamanan: {$sec}, "
                  . "Efisiensi: {$eff}, Overall: {$overall} (predikat: {$badge}). Kekuatan: {$strengths}. "
                  . "Area berkembang: {$weaknesses}. Studi kasus yang dikerjakan: {$recentTitles}.";
            $result = $this->ai->complete($system, [['role' => 'user', 'content' => $user]], 300);
            if ($result !== null) {
                return trim($result);
            }
        }

        // Fallback deterministik — tetap informatif tanpa API key.
        return "Berdasarkan {$count} studi kasus yang diselesaikan, siswa ini meraih skor keseluruhan {$overall}/100 "
             . "(predikat {$badge}). Kekuatan utama ada pada aspek {$strengths}, sementara aspek {$weaknesses} masih "
             . "menjadi area yang perlu ditingkatkan sebelum benar-benar siap menangani proyek produksi secara mandiri.";
    }
}
