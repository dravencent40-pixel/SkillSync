<?php
/**
 * SkillSync AI — Helper Functions
 */

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Silakan masuk terlebih dahulu.');
        redirect('login.php');
    }
}

function require_role(string $role): void
{
    require_login();
    if (current_user()['role'] !== $role) {
        flash('error', 'Kamu tidak memiliki akses ke halaman ini.');
        redirect('dashboard.php');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function badge_from_score(int $score): string
{
    if ($score >= 90) return 'Top Talent';
    if ($score >= 75) return 'Job Ready';
    if ($score >= 55) return 'Junior Ready';
    return 'Pemula';
}

function score_color_class(int $score): string
{
    if ($score >= 85) return 'text-emerald-600';
    if ($score >= 65) return 'text-amber-600';
    return 'text-rose-600';
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    return floor($diff / 86400) . ' hari lalu';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $init = strtoupper(substr($parts[0] ?? 'U', 0, 1));
    if (isset($parts[1])) {
        $init .= strtoupper(substr($parts[1], 0, 1));
    }
    return $init;
}

// ---------------------------------------------------------------------
// CSRF protection — token per sesi, dipakai di semua form POST penting.
// ---------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    $valid = !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
    if (!$valid) {
        flash('error', 'Sesi form sudah kedaluwarsa, silakan coba lagi.');
    }
    return $valid;
}

// ---------------------------------------------------------------------
// Activity log — jejak aksi tiap Agent (Task Issuer, Reviewer, Mentor,
// Profile Generator) supaya alur kerja "multi-agent" terlihat nyata,
// bukan cuma klaim. Ditulis ke tabel activity_logs.
// ---------------------------------------------------------------------
function log_activity(?int $userId, string $action, ?string $meta = null): void
{
    try {
        db()->prepare('INSERT INTO activity_logs (user_id, action, meta) VALUES (?, ?, ?)')
            ->execute([$userId, $action, $meta]);
    } catch (\Throwable $e) {
        error_log('log_activity gagal: ' . $e->getMessage());
    }
}

/** Label & ikon SVG-path per jenis aksi, untuk dipakai di timeline UI. */
function activity_label(string $action): array
{
    $map = [
        'task_recommended'    => ['Agent Task Issuer merekomendasikan studi kasus', '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/>'],
        'submission_reviewed' => ['Agent Reviewer & Auditor menyelesaikan audit', '<path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>'],
        'mentor_reply'        => ['Agent Mentor membalas', '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
        'profile_regenerated' => ['Agent Profile Generator memperbarui profil kompetensi', '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
        'login'                => ['Masuk ke SkillSync AI', '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/>'],
    ];
    return $map[$action] ?? [$action, '<circle cx="12" cy="12" r="10"/>'];
}

function recent_activity(int $userId, int $limit = 6): array
{
    $stmt = db()->prepare('SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ' . (int) $limit);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/** Status mode AI saat ini — dipakai untuk badge transparansi di header/dashboard. */
function ai_mode(): array
{
    static $client = null;
    if ($client === null) {
        require_once __DIR__ . '/agents/AIClient.php';
        $client = new AIClient();
    }
    return $client->isAvailable()
        ? ['label' => 'Groq AI Aktif', 'active' => true]
        : ['label' => 'Mode Heuristik Lokal', 'active' => false];
}
