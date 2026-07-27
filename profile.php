<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_role('siswa');
$user = current_user();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_public'])) {
    $pdo->prepare('UPDATE skill_profiles SET is_public = 1 - is_public WHERE user_id = ?')->execute([$user['id']]);
    redirect('profile.php');
}

$stmt = $pdo->prepare(
    'SELECT sp.*, s.jurusan, s.kelas, s.sekolah FROM skill_profiles sp
     LEFT JOIN student_profiles s ON s.user_id = sp.user_id
     WHERE sp.user_id = ?'
);
$stmt->execute([$user['id']]);
$profile = $stmt->fetch() ?: ['overall_score'=>0,'clean_code_avg'=>0,'security_avg'=>0,'efficiency_avg'=>0,'tasks_completed'=>0,'badge'=>'Pemula','strengths'=>null,'weaknesses'=>null,'is_public'=>1,'jurusan'=>null,'kelas'=>null,'sekolah'=>null];

$tracksStmt = $pdo->prepare(
    'SELECT spt.*, c.name AS category_name, c.rubric_criteria
     FROM skill_profile_tracks spt
     JOIN task_categories c ON c.id = spt.category_id
     WHERE spt.user_id = ? AND spt.tasks_completed > 0
     ORDER BY spt.tasks_completed DESC, spt.overall_score DESC'
);
$tracksStmt->execute([$user['id']]);
$tracks = $tracksStmt->fetchAll();

$historyStmt = $pdo->prepare(
    'SELECT s.id, s.submitted_at, t.title, r.overall_score, r.clean_code_score, r.security_score, r.efficiency_score
     FROM submissions s JOIN tasks t ON t.id = s.task_id
     LEFT JOIN ai_reviews r ON r.submission_id = s.id
     WHERE s.user_id = ? ORDER BY s.submitted_at DESC'
);
$historyStmt->execute([$user['id']]);
$history = $historyStmt->fetchAll();

$pageTitle = 'Profil Skill';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-4xl mx-auto px-6 py-10">
  <!-- Profile Header -->
  <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 animate-fade-up">
    <div class="flex items-center gap-5">
      <div class="avatar avatar-xl" style="background: var(--gradient-dark); box-shadow: 0 4px 16px rgba(0,0,0,0.2);">
        <?= e(initials($user['name'])) ?>
      </div>
      <div>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tight"><?= e($user['name']) ?></h1>
        <p class="text-sm text-[var(--muted)] mt-1"><?= e($profile['jurusan'] ?: '-') ?> &middot; <?= e($profile['sekolah'] ?: 'SMKN 9 Bekasi') ?></p>
        <div class="mt-2">
          <span class="badge <?= $profile['is_public'] ? 'badge-info' : 'badge-accent' ?>">
            <?= $profile['is_public'] ? '● Terlihat oleh mitra' : '○ Tersembunyi dari mitra' ?>
          </span>
        </div>
      </div>
    </div>
    <form method="POST" class="shrink-0">
      <input type="hidden" name="toggle_public" value="1">
      <button type="submit" class="btn <?= $profile['is_public'] ? 'btn-primary' : 'btn-ghost' ?> btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        <?= $profile['is_public'] ? 'Publik' : 'Privat' ?>
      </button>
    </form>
  </div>

  <!-- Score Overview -->
  <div class="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-6 stagger">
    <div class="surface spot-card p-8 flex items-center gap-6 lg:col-span-1 rounded-3xl">
      <div class="relative w-24 h-24 shrink-0">
        <svg class="score-ring w-24 h-24" data-score="<?= (int)$profile['overall_score'] ?>" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0" stroke-width="8"/>
          <circle class="progress" cx="50" cy="50" r="42" fill="none" stroke="url(#profileGradient)" stroke-width="8" stroke-linecap="round"/>
          <defs>
            <linearGradient id="profileGradient" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" style="stop-color:#0a0a0a"/>
              <stop offset="100%" style="stop-color:#525252"/>
            </linearGradient>
          </defs>
        </svg>
        <div class="absolute inset-0 grid place-items-center">
          <span class="text-xl font-extrabold text-[var(--ink)]"><?= (int)$profile['overall_score'] ?></span>
        </div>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Badge</p>
        <p class="mt-1 text-lg font-bold"><?= e($profile['badge']) ?></p>
      </div>
    </div>

    <div class="lg:col-span-2 surface p-8 rounded-3xl">
      <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)] mb-4">Skor Per Divisi</p>
      <?php if (empty($tracks)): ?>
        <p class="text-sm text-[var(--muted)]">Belum ada studi kasus yang diselesaikan.</p>
      <?php else: ?>
        <div class="space-y-4 divide-y divide-[var(--border-light)]">
          <?php foreach ($tracks as $i => $track): $rubric = task_rubric($track); ?>
          <div class="<?= $i > 0 ? 'pt-4' : '' ?>">
            <div class="flex items-center justify-between mb-3">
              <span class="badge badge-info"><?= e($track['category_name']) ?></span>
              <span class="text-sm font-bold <?= score_color_class((int)$track['overall_score']) ?>"><?= (int)$track['overall_score'] ?>/100</span>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div class="text-center">
                <p class="text-lg font-extrabold <?= score_color_class((int)$track['criterion1_score']) ?>"><?= (int)$track['criterion1_score'] ?></p>
                <p class="text-[11px] text-[var(--muted)] mt-0.5 leading-tight"><?= e($rubric[0]['label']) ?></p>
              </div>
              <div class="text-center">
                <p class="text-lg font-extrabold <?= score_color_class((int)$track['criterion2_score']) ?>"><?= (int)$track['criterion2_score'] ?></p>
                <p class="text-[11px] text-[var(--muted)] mt-0.5 leading-tight"><?= e($rubric[1]['label']) ?></p>
              </div>
              <div class="text-center">
                <p class="text-lg font-extrabold <?= score_color_class((int)$track['criterion3_score']) ?>"><?= (int)$track['criterion3_score'] ?></p>
                <p class="text-[11px] text-[var(--muted)] mt-0.5 leading-tight"><?= e($rubric[2]['label']) ?></p>
              </div>
            </div>
            <p class="text-[11px] text-[var(--muted-light)] mt-2"><?= (int)$track['tasks_completed'] ?> studi kasus &middot; pemahaman rata-rata <?= (int)$track['comprehension_avg'] ?>/100</p>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Strengths & Weaknesses -->
  <?php if ($profile['strengths']): ?>
  <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 stagger">
    <div class="surface p-6 rounded-2xl">
      <div class="flex items-center gap-2 mb-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: #f5f5f5; color: #0a0a0a;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
        </div>
        <p class="text-xs font-semibold uppercase tracking-wider text-[#0a0a0a]">Kekuatan Utama</p>
      </div>
      <p class="font-semibold text-[var(--ink)]"><?= e($profile['strengths']) ?></p>
    </div>
    <div class="surface p-6 rounded-2xl">
      <div class="flex items-center gap-2 mb-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: #f5f5f5; color: #525252;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
        </div>
        <p class="text-xs font-semibold uppercase tracking-wider text-[#525252]">Perlu Ditingkatkan</p>
      </div>
      <p class="font-semibold text-[var(--ink)]"><?= e($profile['weaknesses']) ?></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- History -->
  <div class="mt-12">
    <h2 class="text-lg font-bold mb-5">Riwayat Penilaian</h2>
    <?php if (empty($history)): ?>
      <div class="surface rounded-3xl p-12">
        <div class="empty-state">
          <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <p class="empty-state-title">Belum ada riwayat</p>
          <p class="empty-state-desc">Mulai kerjakan studi kasus pertamamu.</p>
        </div>
      </div>
    <?php else: ?>
    <div class="surface rounded-3xl overflow-hidden divide-y divide-[var(--border-light)]">
      <?php foreach ($history as $h): ?>
      <a href="<?= APP_URL ?>/submission.php?id=<?= $h['id'] ?>" class="flex items-center justify-between px-6 py-4 transition-colors hover:bg-[#f5f5f5] group">
        <div class="flex items-center gap-4">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center <?= $h['overall_score'] !== null ? ($h['overall_score'] >= 80 ? 'bg-neutral-100' : ($h['overall_score'] >= 60 ? 'bg-neutral-100' : 'bg-red-50')) : 'bg-slate-50' ?>">
            <?php if ($h['overall_score'] !== null): ?>
              <span class="text-sm font-bold <?= score_color_class((int)$h['overall_score']) ?>"><?= (int)$h['overall_score'] ?></span>
            <?php else: ?>
              <div class="w-3 h-3 rounded-full bg-slate-300 animate-pulse"></div>
            <?php endif; ?>
          </div>
          <div>
            <p class="font-medium text-[var(--ink)] text-sm group-hover:text-[#0a0a0a] transition-colors"><?= e($h['title']) ?></p>
            <p class="text-xs text-[var(--muted-light)] mt-0.5"><?= time_ago($h['submitted_at']) ?></p>
          </div>
        </div>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted-light)" stroke-width="2" class="opacity-0 group-hover:opacity-100 transition-opacity"><polyline points="9 18 15 12 9 6"/></svg>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
