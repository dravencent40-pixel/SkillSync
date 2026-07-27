<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('mitra');
$user = current_user();
$pdo = db();

$search = trim($_GET['q'] ?? '');

$sql = "SELECT u.id, u.name, u.avatar_initial, sp.overall_score, sp.badge, sp.tasks_completed,
               sp.strengths, s.jurusan, s.sekolah, s.cv_path
        FROM users u
        JOIN skill_profiles sp ON sp.user_id = u.id
        LEFT JOIN student_profiles s ON s.user_id = u.id
        WHERE u.role = 'siswa' AND u.is_active = 1 AND sp.is_public = 1 AND sp.tasks_completed > 0";
$params = [];
if ($search !== '') {
    $sql .= " AND (u.name LIKE ? OR s.jurusan LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
$sql .= " ORDER BY sp.overall_score DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$talents = $stmt->fetchAll();

$pageTitle = 'Talent Pool';
require __DIR__ . '/../includes/header.php';
?>

<section class="max-w-7xl mx-auto px-6 py-10">
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 animate-fade-up">
    <div>
      <h1 class="text-3xl font-bold tracking-tight">Talent Pool</h1>
      <p class="mt-1 text-sm text-[var(--muted)]">Siswa dengan profil kompetensi publik, diurutkan dari skor keseluruhan tertinggi.</p>
    </div>
    <form method="GET" class="shrink-0 w-full md:w-auto">
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari nama atau jurusan..." class="w-full md:w-64">
    </form>
  </div>

  <?php if (empty($talents)): ?>
    <div class="mt-10 surface rounded-3xl p-14">
      <div class="empty-state">
        <div class="empty-state-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <p class="empty-state-title"><?= $search !== '' ? 'Tidak ada hasil' : 'Belum ada talenta publik' ?></p>
        <p class="empty-state-desc"><?= $search !== '' ? 'Coba kata kunci lain.' : 'Talenta akan muncul di sini setelah siswa menyelesaikan studi kasus dan profilnya publik.' ?></p>
      </div>
    </div>
  <?php else: ?>
  <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 stagger">
    <?php foreach ($talents as $t): ?>
    <a href="<?= APP_URL ?>/company/talent-detail.php?id=<?= $t['id'] ?>" class="cv-card group block">
      <div class="flex items-start gap-3">
        <div class="avatar avatar-lg shrink-0"><?= e($t['avatar_initial'] ?: initials($t['name'])) ?></div>
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="font-semibold text-[var(--ink)] truncate group-hover:text-[var(--accent)] transition-colors"><?= e($t['name']) ?></p>
              <p class="text-xs text-[var(--muted)] truncate"><?= e($t['jurusan'] ?: 'Jurusan belum diisi') ?> &middot; <?= e($t['sekolah'] ?: '-') ?></p>
            </div>
            <span class="text-sm font-bold <?= score_color_class((int)$t['overall_score']) ?> shrink-0"><?= (int)$t['overall_score'] ?></span>
          </div>
          <div class="mt-3 flex items-center justify-between">
            <span class="badge badge-accent"><?= e($t['badge']) ?></span>
            <span class="text-[11px] text-[var(--muted-light)]"><?= (int)$t['tasks_completed'] ?> studi kasus</span>
          </div>
          <?php if ($t['cv_path']): ?>
            <p class="mt-2 text-[11px] font-medium flex items-center gap-1" style="color: var(--success);">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
              CV tersedia
            </p>
          <?php endif; ?>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
