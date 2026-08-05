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
      <div class="avatar avatar-xl text-white" style="background: var(--gradient-accent); box-shadow: var(--shadow-accent);">
        <?= e(initials($user['name'])) ?>
      </div>
      <div>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tight"><?= e($user['name']) ?></h1>
        <p class="text-sm text-[var(--muted)] mt-1"><?= e($profile['jurusan'] ?: '-') ?> &middot; <?= e($profile['sekolah'] ?: 'SMKN 9 Bekasi') ?></p>
        <div class="mt-2">
          <span class="badge badge-accent">
            <?= $profile['is_public'] ? '● Terlihat oleh mitra' : '○ Tersembunyi dari mitra' ?>
          </span>
        </div>
      </div>
    </div>
    <div class="flex items-center gap-3 shrink-0">
      <a href="<?= APP_URL ?>/upload_cv.php" class="btn btn-ghost btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
        Unggah CV
      </a>
      <form method="POST">
        <input type="hidden" name="toggle_public" value="1">
        <button type="submit" class="btn <?= $profile['is_public'] ? 'btn-primary' : 'btn-ghost' ?> btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          <?= $profile['is_public'] ? 'Publik' : 'Privat' ?>
        </button>
      </form>
    </div>
  </div>

  <!-- Score Overview -->
  <div class="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-6 stagger">
    <div class="surface spot-card p-8 flex items-center gap-6 lg:col-span-1">
      <div class="relative w-24 h-24 shrink-0">
        <svg class="score-ring w-24 h-24" data-score="<?= (int)$profile['overall_score'] ?>" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="42" fill="none" stroke="var(--border)" stroke-width="8"/>
          <circle class="progress" cx="50" cy="50" r="42" fill="none" stroke="var(--accent)" stroke-width="8" stroke-linecap="round"/>
        </svg>
        <div class="absolute inset-0 grid place-items-center">
          <span class="num text-xl font-extrabold text-[var(--ink)]"><?= (int)$profile['overall_score'] ?></span>
        </div>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Badge</p>
        <p class="mt-1 text-lg font-bold"><?= e($profile['badge']) ?></p>
      </div>
    </div>

    <div class="lg:col-span-2 surface p-8">
      <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)] mb-5">Skor Per Divisi</p>
      <?php if (empty($tracks)): ?>
        <p class="text-sm text-[var(--muted)]">Belum ada studi kasus yang diselesaikan.</p>
      <?php else: ?>
        <div class="space-y-6">
          <?php foreach ($tracks as $i => $track):
            $rubric = task_rubric($track);
            $criteria = [
                ['score' => $track['criterion1_score'], 'label' => $rubric[0]['label']],
                ['score' => $track['criterion2_score'], 'label' => $rubric[1]['label']],
                ['score' => $track['criterion3_score'], 'label' => $rubric[2]['label']],
            ];
          ?>
          <div>
            <div class="flex items-center justify-between mb-4">
              <span class="badge badge-accent"><?= e($track['category_name']) ?></span>
              <span class="num text-sm font-bold <?= score_color_class((int)$track['overall_score']) ?>"><?= (int)$track['overall_score'] ?>/100</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4">
              <?php foreach ($criteria as $c): ?>
              <div>
                <div class="flex items-center justify-between mb-1.5">
                  <p class="text-[11px] text-[var(--muted)] leading-tight"><?= e($c['label']) ?></p>
                  <span class="num text-sm font-bold <?= score_color_class((int)$c['score']) ?>"><?= (int)$c['score'] ?></span>
                </div>
                <div class="mini-bar"><span style="width: <?= max(2, min(100, (int)$c['score'])) ?>%; animation-delay: <?= $i * 0.08 + 0.1 ?>s;"></span></div>
              </div>
              <?php endforeach; ?>
            </div>
            <p class="text-[11px] text-[var(--muted-light)] mt-3"><?= (int)$track['tasks_completed'] ?> studi kasus &middot; pemahaman rata-rata <span class="num"><?= (int)$track['comprehension_avg'] ?></span>/100</p>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Strengths & Weaknesses -->
  <?php if ($profile['strengths']): ?>
  <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 stagger">
    <div class="surface p-6">
      <div class="flex items-center gap-2 mb-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)] border border-[var(--accent-100)]">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/></svg>
        </div>
        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--ink)]">Kekuatan Utama</p>
      </div>
      <p class="font-semibold text-[var(--ink)]"><?= e($profile['strengths']) ?></p>
    </div>
    <div class="surface p-6">
      <div class="flex items-center gap-2 mb-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--warning-50)] text-[var(--warning)] border border-[#f6e3c3]">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/></svg>
        </div>
        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--warning)]">Perlu Ditingkatkan</p>
      </div>
      <p class="font-semibold text-[var(--ink)]"><?= e($profile['weaknesses']) ?></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- History -->
  <div class="mt-12">
    <h2 class="text-lg font-bold mb-5">Riwayat Penilaian</h2>
    <?php if (empty($history)): ?>
      <div class="surface p-12">
        <div class="empty-state">
          <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <p class="empty-state-title">Belum ada riwayat</p>
          <p class="empty-state-desc">Mulai kerjakan studi kasus pertamamu.</p>
        </div>
      </div>
    <?php else: ?>
    <div class="surface overflow-hidden divide-y divide-[var(--border-light)]">
      <?php foreach ($history as $h): ?>
      <a href="<?= APP_URL ?>/submission.php?id=<?= $h['id'] ?>" class="flex items-center justify-between px-6 py-4 transition-colors hover:bg-[var(--paper-soft)] group">
        <div class="flex items-center gap-4">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center <?= $h['overall_score'] !== null ? ($h['overall_score'] >= 60 ? 'bg-[var(--accent-50)]' : 'bg-[var(--danger-50)]') : 'bg-[var(--paper-soft)]' ?>">
            <?php if ($h['overall_score'] !== null): ?>
              <span class="num text-sm font-bold <?= score_color_class((int)$h['overall_score']) ?>"><?= (int)$h['overall_score'] ?></span>
            <?php else: ?>
              <div class="w-3 h-3 rounded-full bg-[var(--border-strong)] animate-pulse"></div>
            <?php endif; ?>
          </div>
          <div class="min-w-0">
            <p class="font-medium text-[var(--ink)] text-sm"><?= e($h['title']) ?></p>
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
