<?php

use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| SkillSync — Helper Functions (shim legacy)
|--------------------------------------------------------------------------
| Versi Laravel dari helper yang sebelumnya hidup di includes/functions.php.
| Tujuannya: class agent (app/Services/Agents) yang ditulis dengan gaya PHP
| native bisa dipakai apa adanya tanpa di-rewrite — db(), log_activity(),
| task_rubric(), dst. dipanggil sebagai fungsi global seperti dulu.
|
| Catatan: e() dan redirect() TIDAK didefinisikan di sini karena Laravel
| sudah punya global helper dengan nama yang sama.
*/

if (!function_exists('db')) {
    function db(): PDO
    {
        static $pdo = null;
        if ($pdo === null) {
            $pdo = DB::connection()->getPdo();
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return $pdo;
    }
}

if (!function_exists('log_activity')) {
    function log_activity(?int $userId, string $action, ?string $meta = null): void
    {
        try {
            db()->prepare('INSERT INTO activity_logs (user_id, action, meta) VALUES (?, ?, ?)')
                ->execute([$userId, $action, $meta]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

if (!function_exists('task_rubric')) {
    function task_rubric(array $category): array
    {
        $default = [
            ['key' => 'clean_code', 'label' => 'Clean Code', 'description' => 'Penamaan, struktur, komentar, konsistensi'],
            ['key' => 'security', 'label' => 'Keamanan', 'description' => 'Validasi input, penanganan data sensitif'],
            ['key' => 'efficiency', 'label' => 'Efisiensi', 'description' => 'Kompleksitas, redundansi'],
        ];
        $decoded = !empty($category['rubric_criteria']) ? json_decode($category['rubric_criteria'], true) : null;
        if (!is_array($decoded) || count($decoded) < 3) {
            return $default;
        }
        return array_slice($decoded, 0, 3);
    }
}

if (!function_exists('submission_type_config')) {
    function submission_type_config(string $type): array
    {
        $map = [
            'code'    => ['label' => 'Kode Program', 'field_label' => 'Kode/Implementasi', 'accepts_file' => false, 'accepts_link' => false],
            'design'  => ['label' => 'Desain UI/UX', 'field_label' => 'Penjelasan Keputusan Desain', 'accepts_file' => true, 'accepts_link' => true],
            'network' => ['label' => 'Jaringan & Infrastruktur', 'field_label' => 'Dokumentasi Konfigurasi/Topologi', 'accepts_file' => true, 'accepts_link' => false],
            'general' => ['label' => 'Umum', 'field_label' => 'Jawaban/Penjelasan', 'accepts_file' => true, 'accepts_link' => true],
        ];
        return $map[$type] ?? $map['general'];
    }
}

if (!function_exists('badge_from_score')) {
    function badge_from_score(int $score): string
    {
        if ($score >= 90) return 'Top Talent';
        if ($score >= 75) return 'Job Ready';
        if ($score >= 55) return 'Junior Ready';
        return 'Pemula';
    }
}

if (!function_exists('score_color_class')) {
    function score_color_class(int $score): string
    {
        if ($score >= 85) return 'text-emerald-600';
        if ($score >= 65) return 'text-amber-600';
        return 'text-rose-600';
    }
}

if (!function_exists('time_ago')) {
    function time_ago(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60) return 'baru saja';
        if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
        if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
        return floor($diff / 86400) . ' hari lalu';
    }
}

if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $init = strtoupper(substr($parts[0] ?? 'U', 0, 1));
        if (isset($parts[1])) {
            $init .= strtoupper(substr($parts[1], 0, 1));
        }
        return $init;
    }
}

if (!function_exists('activity_label')) {
    function activity_label(string $action): array
    {
        $map = [
            'register'             => ['Akun baru dibuat', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>'],
            'task_created'         => ['Agent Task Issuer menerbitkan studi kasus baru', '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/>'],
            'task_recommended'     => ['Agent Task Issuer merekomendasikan studi kasus', '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/>'],
            'submission_reviewed'  => ['Agent Reviewer & Auditor menyelesaikan audit', '<path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>'],
            'defense_completed'    => ['Agent Defense menilai sesi pembelaan', '<path d="M9.09 9a3 3 0 1 1 5.83 1c0 2-3 3-3 3m.01 4h.01M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z"/>'],
            'mentor_reply'         => ['Agent Mentor membalas', '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
            'profile_regenerated'  => ['Agent Profile Generator memperbarui profil kompetensi', '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
            'cv_uploaded'          => ['CV diperbarui', '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>'],
            'talent_recommend'     => ['Mitra memberi status pada talenta', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
            'login'                => ['Masuk ke SkillSync', '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/>'],
        ];
        return $map[$action] ?? [$action, '<circle cx="12" cy="12" r="10"/>'];
    }
}

if (!function_exists('recent_activity')) {
    function recent_activity(int $userId, int $limit = 6): array
    {
        $stmt = db()->prepare('SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit);
        $stmt->execute([$userId]);
        return array_map(function ($row) {
            [$label, $icon] = activity_label($row['action']);
            $row['label'] = $label;
            $row['icon'] = $icon;
            $row['time_ago'] = time_ago($row['created_at']);
            return $row;
        }, $stmt->fetchAll());
    }
}

if (!function_exists('ai_mode')) {
    function ai_mode(): array
    {
        static $client = null;
        if ($client === null) {
            $client = new \App\Services\Agents\AIClient();
        }
        return $client->isAvailable()
            ? ['label' => 'Groq AI Aktif', 'active' => true]
            : ['label' => 'Mode Heuristik Lokal', 'active' => false];
    }
}
