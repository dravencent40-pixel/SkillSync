<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/activity_timeline.php';
require_once __DIR__ . '/includes/agents/TaskIssuerAgent.php';
require_login();
$user = current_user();
$pdo = db();
$pageTitle = 'Dashboard';
$activity = recent_activity($user['id'], 6);

if ($user['role'] === 'siswa') {
    $profileStmt = $pdo->prepare('SELECT * FROM skill_profiles WHERE user_id = ?');
    $profileStmt->execute([$user['id']]);
    $profile = $profileStmt->fetch() ?: ['overall_score'=>0,'clean_code_avg'=>0,'security_avg'=>0,'efficiency_avg'=>0,'tasks_completed'=>0,'badge'=>'Pemula'];

    $tracksStmt = $pdo->prepare(
        'SELECT spt.*, c.name AS category_name, c.rubric_criteria
         FROM skill_profile_tracks spt
         JOIN task_categories c ON c.id = spt.category_id
         WHERE spt.user_id = ? AND spt.tasks_completed > 0
         ORDER BY spt.tasks_completed DESC, spt.overall_score DESC'
    );
    $tracksStmt->execute([$user['id']]);
    $tracks = $tracksStmt->fetchAll();

    $recentStmt = $pdo->prepare(
        'SELECT s.id, s.submitted_at, t.title, r.overall_score
         FROM submissions s JOIN tasks t ON t.id = s.task_id
         LEFT JOIN ai_reviews r ON r.submission_id = s.id
         WHERE s.user_id = ? ORDER BY s.submitted_at DESC LIMIT 5'
    );
    $recentStmt->execute([$user['id']]);
    $recent = $recentStmt->fetchAll();

    $recommendation = (new TaskIssuerAgent())->recommend($user['id'], 3);
    $recommended = $recommendation['tasks'];
    $recommendReason = $recommendation['reason'];
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($user['role'] === 'siswa'): ?>
<section class="max-w-7xl mx-auto px-6 py-10">
  <!-- Welcome Banner -->
  <div class="welcome-banner animate-fade-up">
    <div class="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <p class="text-sm text-[#c9c8bd] font-medium">Selamat datang kembali,</p>
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-white mt-1"><?= e($user['name']) ?></h1>
        <p class="mt-2 text-sm text-[#a3a298] max-w-md">Terus asah kemampuanmu dengan mengerjakan studi kasus industri baru. Skor kompetensimu akan terus diperbarui oleh AI.</p>
      </div>
      <a href="<?= APP_URL ?>/tasks.php" class="btn btn-primary shrink-0">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
        Ambil Studi Kasus Baru
      </a>
    </div>
  </div>

  <!-- Score Overview -->
  <div class="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-6 stagger">
    <!-- Score Ring Card -->
    <div class="surface spot-card p-8 flex items-center gap-6 lg:col-span-1">
      <div class="relative w-28 h-28 shrink-0">
        <svg class="score-ring w-28 h-28" data-score="<?= (int)$profile['overall_score'] ?>" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="42" fill="none" stroke="var(--border)" stroke-width="8"/>
          <circle class="progress" cx="50" cy="50" r="42" fill="none" stroke="var(--accent)" stroke-width="8" stroke-linecap="round"/>
        </svg>
        <div class="absolute inset-0 grid place-items-center">
          <span class="num text-3xl font-extrabold text-[var(--ink)]"><?= (int)$profile['overall_score'] ?></span>
        </div>
      </div>
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Skor Keseluruhan</p>
        <p class="mt-1 text-lg font-bold text-[var(--ink)]"><?= e($profile['badge']) ?></p>
        <p class="text-xs text-[var(--muted)] mt-1 leading-snug"><?= (int)$profile['tasks_completed'] ?> studi kasus &middot; lintas semua divisi</p>
      </div>
    </div>

    <!-- Breakdown Skor Per Divisi -->
    <div class="lg:col-span-2 surface p-8">
      <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)] mb-5">Skor Per Divisi</p>
      <?php if (empty($tracks)): ?>
        <div class="empty-state py-4">
          <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
          </div>
          <p class="empty-state-title">Belum ada studi kasus selesai</p>
          <p class="empty-state-desc">Kerjakan studi kasus pertamamu untuk melihat breakdown skor per divisi di sini.</p>
        </div>
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
            <p class="text-[11px] text-[var(--muted-light)] mt-3"><?= (int)$track['tasks_completed'] ?> studi kasus &middot; skor pemahaman rata-rata <span class="num"><?= (int)$track['comprehension_avg'] ?></span>/100</p>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recommended Tasks -->
  <div class="mt-12">
    <div class="flex items-center justify-between mb-5">
      <div>
        <h2 class="text-lg font-bold text-[var(--ink)]">Direkomendasikan untukmu</h2>
        <p class="text-xs text-[var(--muted)] mt-0.5"><?= $recommendReason ? e($recommendReason) : 'Dipilih oleh Agent Task Issuer berdasarkan kelemahan skill kamu' ?></p>
      </div>
      <a href="<?= APP_URL ?>/tasks.php" class="link-accent text-sm">Lihat semua</a>
    </div>
    <?php if (empty($recommended)): ?>
      <div class="surface p-12">
        <div class="empty-state">
          <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
          </div>
          <p class="empty-state-title">Semua studi kasus sudah dikerjakan</p>
          <p class="empty-state-desc">Nantikan task baru dari mitra industri!</p>
        </div>
      </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 stagger">
      <?php foreach ($recommended as $t): ?>
      <a href="<?= APP_URL ?>/task.php?id=<?= $t['id'] ?>" class="surface surface-hover spot-card p-6 group">
        <span class="badge badge-accent"><?= e($t['category_name']) ?></span>
        <h3 class="mt-4 font-semibold text-[var(--ink)] leading-snug"><?= e($t['title']) ?></h3>
        <p class="mt-2 text-xs text-[var(--muted)] capitalize flex items-center gap-2">
          <span class="w-1.5 h-1.5 rounded-full <?= $t['difficulty']==='mahir' ? 'bg-[var(--danger)]' : ($t['difficulty']==='menengah' ? 'bg-[var(--warning)]' : 'bg-[var(--accent)]') ?>"></span>
          <?= e($t['difficulty']) ?>
          <span class="text-[var(--border)]">&middot;</span>
          <?= e($t['industry_context']) ?>
        </p>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Recent Submissions -->
  <div class="mt-12">
    <h2 class="text-lg font-bold text-[var(--ink)] mb-5">Riwayat Submission</h2>
    <?php if (empty($recent)): ?>
      <div class="surface p-12">
        <div class="empty-state">
          <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <p class="empty-state-title">Belum ada submission</p>
          <p class="empty-state-desc">Ambil studi kasus pertamamu sekarang.</p>
        </div>
      </div>
    <?php else: ?>
    <div class="surface overflow-hidden divide-y divide-[var(--border-light)]">
      <?php foreach ($recent as $r): ?>
      <a href="<?= APP_URL ?>/submission.php?id=<?= $r['id'] ?>" class="flex items-center justify-between px-6 py-4 transition-colors hover:bg-[var(--paper-soft)] group">
        <div class="flex items-center gap-4">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center <?= $r['overall_score'] !== null ? ($r['overall_score'] >= 60 ? 'bg-[var(--accent-50)]' : 'bg-[var(--danger-50)]') : 'bg-[var(--paper-soft)]' ?>">
            <?php if ($r['overall_score'] !== null): ?>
              <span class="num text-sm font-bold <?= score_color_class((int)$r['overall_score']) ?>"><?= (int)$r['overall_score'] ?></span>
            <?php else: ?>
              <div class="w-3 h-3 rounded-full bg-[var(--border-strong)] animate-pulse"></div>
            <?php endif; ?>
          </div>
          <div class="min-w-0">
            <p class="font-medium text-[var(--ink)] text-sm"><?= e($r['title']) ?></p>
            <p class="text-xs text-[var(--muted-light)] mt-0.5"><?= time_ago($r['submitted_at']) ?></p>
          </div>
        </div>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-0 group-hover:opacity-100 transition-opacity"><polyline points="9 18 15 12 9 6"/></svg>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Agent Activity Timeline -->
  <div class="mt-12">
    <div class="flex items-center justify-between mb-5">
      <div>
        <h2 class="text-lg font-bold text-[var(--ink)]">Aktivitas Agent</h2>
        <p class="text-xs text-[var(--muted)] mt-0.5">Jejak kerja Task Issuer, Reviewer &amp; Auditor, Mentor, dan Profile Generator untukmu</p>
      </div>
    </div>
    <?php render_activity_timeline($activity); ?>
  </div>
</section>

<?php else: /* ============ MITRA DASHBOARD ============ */
    $taskCount = $pdo->query('SELECT COUNT(*) c FROM tasks')->fetch()['c'];
    $submissionCount = $pdo->query('SELECT COUNT(*) c FROM submissions')->fetch()['c'];
    $topTalents = $pdo->query(
        "SELECT u.id, u.name, sp.overall_score, sp.badge, sp.tasks_completed
         FROM skill_profiles sp JOIN users u ON u.id = sp.user_id
         WHERE sp.tasks_completed > 0 ORDER BY sp.overall_score DESC LIMIT 5"
    )->fetchAll();
?>
<section class="max-w-7xl mx-auto px-6 py-10">
  <!-- Welcome Banner -->
  <div class="welcome-banner animate-fade-up">
    <div class="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div>
        <p class="text-sm text-[#c9c8bd] font-medium">Dashboard Mitra</p>
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-white mt-1"><?= e($user['name']) ?></h1>
        <p class="mt-2 text-sm text-[#a3a298] max-w-md">Kelola studi kasus dan temukan talenta terbaik dari pool siswa SMK yang sudah terverifikasi kompetensinya.</p>
      </div>
      <a href="<?= APP_URL ?>/tasks.php" class="btn btn-primary shrink-0">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
         Terbitkan Studi Kasus
      </a>
    </div>
  </div>

  <!-- Stats -->
  <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6 stagger">
    <div class="stat-card group">
      <div class="flex items-center justify-between mb-5">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)] border border-[var(--accent-100)] transition-transform duration-300 group-hover:scale-110">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <span class="badge badge-accent">Aktif</span>
      </div>
      <p class="stat-num text-[var(--ink)]"><?= (int)$taskCount ?></p>
      <p class="stat-label mt-1.5">Studi kasus aktif</p>
    </div>

    <div class="stat-card group">
      <div class="flex items-center justify-between mb-5">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)] border border-[var(--accent-100)] transition-transform duration-300 group-hover:scale-110">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
        </div>
        <span class="badge badge-accent">AI</span>
      </div>
      <p class="stat-num text-[var(--ink)]"><?= (int)$submissionCount ?></p>
      <p class="stat-label mt-1.5">Total submission dinilai AI</p>
    </div>

    <div class="stat-card group">
      <div class="flex items-center justify-between mb-5">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)] border border-[var(--accent-100)] transition-transform duration-300 group-hover:scale-110">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <span class="badge badge-accent">Pool</span>
      </div>
      <p class="stat-num text-[var(--ink)]"><?= count($topTalents) ?></p>
      <p class="stat-label mt-1.5">Talenta dengan profil aktif</p>
    </div>
  </div>

  <!-- Top Talent Pool -->
  <div class="mt-12">
    <div class="flex items-center justify-between mb-5">
      <h2 class="text-lg font-bold text-[var(--ink)]">Top Talent Pool</h2>
      <a href="<?= APP_URL ?>/company/talent.php" class="link-accent text-sm">Lihat semua talenta</a>
    </div>
    <?php if (empty($topTalents)): ?>
      <div class="surface p-12">
        <div class="empty-state">
          <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <p class="empty-state-title">Belum ada siswa</p>
          <p class="empty-state-desc">Belum ada siswa yang menyelesaikan studi kasus.</p>
        </div>
      </div>
    <?php else: ?>
    <div class="surface overflow-hidden divide-y divide-[var(--border-light)]">
      <?php foreach ($topTalents as $i => $t): ?>
      <a href="<?= APP_URL ?>/company/talent-detail.php?id=<?= $t['id'] ?>" class="flex items-center justify-between px-6 py-4 transition-colors hover:bg-[var(--paper-soft)] group">
        <div class="flex items-center gap-4">
          <div class="relative">
            <span class="avatar avatar-md"><?= e(initials($t['name'])) ?></span>
            <?php if ($i < 3): ?>
              <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold <?= $i === 0 ? 'text-white' : 'text-[var(--accent-dark)]' ?>" style="background: <?= $i === 0 ? 'var(--gradient-accent)' : 'var(--accent-100)' ?>; border: 2px solid var(--surface);"><?= $i + 1 ?></span>
            <?php endif; ?>
          </div>
          <div class="min-w-0">
            <p class="font-medium text-[var(--ink)] text-sm"><?= e($t['name']) ?></p>
            <p class="text-xs text-[var(--muted-light)]"><?= e($t['badge']) ?> &middot; <span class="num"><?= (int)$t['tasks_completed'] ?></span> task</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span class="num font-bold <?= score_color_class((int)$t['overall_score']) ?>"><?= (int)$t['overall_score'] ?></span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted-light)" stroke-width="2" class="opacity-0 group-hover:opacity-100 transition-opacity"><polyline points="9 18 15 12 9 6"/></svg>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Agent Activity Timeline -->
  <div class="mt-12">
    <div class="flex items-center justify-between mb-5">
      <div>
        <h2 class="text-lg font-bold text-[var(--ink)]">Aktivitas Agent</h2>
        <p class="text-xs text-[var(--muted)] mt-0.5">Jejak aksi sistem multi-agent terkait akunmu</p>
      </div>
    </div>
    <?php render_activity_timeline($activity); ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
