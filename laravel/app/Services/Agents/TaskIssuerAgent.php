<?php

namespace App\Services\Agents;

/**
 * SkillSync — Agent Task Issuer
 *
 * Bertugas memilih/merekomendasikan studi kasus dari bank soal (tabel `tasks`)
 * yang paling relevan untuk siswa tertentu, diprioritaskan pada kategori yang
 * skornya paling lemah (personalized learning path). Task bank sendiri diisi
 * oleh mitra industri lewat halaman tasks.php — agent berperan memilih urutan
 * penyajian yang paling bermanfaat bagi tiap siswa.
 */
class TaskIssuerAgent
{
    /**
     * @return array{tasks: array, reason: ?string} tasks + alasan personalisasi
     *         (label area terlemah) supaya siswa tahu KENAPA studi kasus ini
     *         yang disodorkan — bukan daftar acak.
     */
    public function recommend(int $userId, int $limit = 3): array
    {
        $pdo = db();

        $stmt = $pdo->prepare('SELECT clean_code_avg, security_avg, efficiency_avg, tasks_completed FROM skill_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();

        $weakCategorySlug = null;
        $reason = null;
        $labels = [
            'keamanan-aplikasi' => 'Keamanan',
            'data-backend'      => 'Efisiensi/Backend',
            'web-development'   => 'Clean Code',
        ];

        if ($profile && (int) $profile['tasks_completed'] > 0) {
            $areas = [
                'keamanan-aplikasi' => (int) $profile['security_avg'],
                'data-backend'      => (int) $profile['efficiency_avg'],
                'web-development'   => (int) $profile['clean_code_avg'],
            ];
            asort($areas);
            $weakCategorySlug = array_key_first($areas);
            $weakScore = reset($areas);
            $reason = "Direkomendasikan karena skor {$labels[$weakCategorySlug]} kamu masih {$weakScore}/100 — area paling perlu ditingkatkan saat ini.";
        }

        $sql = "SELECT t.*, c.name AS category_name, c.slug AS category_slug
                FROM tasks t
                JOIN task_categories c ON c.id = t.category_id
                WHERE t.is_active = 1
                  AND t.id NOT IN (SELECT task_id FROM submissions WHERE user_id = ?)";
        $params = [$userId];

        if ($weakCategorySlug) {
            $sql .= " ORDER BY (c.slug = ?) DESC, t.created_at DESC";
            $params[] = $weakCategorySlug;
        } else {
            $sql .= " ORDER BY t.created_at DESC";
        }
        $sql .= " LIMIT " . (int) $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();

        if ($tasks) {
            log_activity($userId, 'task_recommended', $reason ?? 'Rekomendasi awal (belum ada riwayat submission).');
        }

        return ['tasks' => $tasks, 'reason' => $reason];
    }

    /** @deprecated kompatibilitas — gunakan recommend(). */
    public function recommendedTasks(int $userId, int $limit = 3): array
    {
        return $this->recommend($userId, $limit)['tasks'];
    }
}
