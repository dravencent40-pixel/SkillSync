<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = current_user();
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT s.*, t.title AS task_title, t.case_brief, u.name AS student_name,
            c.submission_type, c.rubric_criteria
     FROM submissions s
     JOIN tasks t ON t.id = s.task_id
     JOIN task_categories c ON c.id = t.category_id
     JOIN users u ON u.id = s.user_id
     WHERE s.id = ?'
);
$stmt->execute([$id]);
$submission = $stmt->fetch();

if (!$submission || ($user['role'] === 'siswa' && $submission['user_id'] != $user['id'])) {
    flash('error', 'Submission tidak ditemukan.');
    redirect('dashboard.php');
}

$rubric = task_rubric($submission);
$submissionType = $submission['submission_type'] ?: 'code';
$isImageFile = $submission['file_path'] && preg_match('/\.(png|jpe?g|webp)$/i', $submission['file_path']);

$reviewStmt = $pdo->prepare('SELECT * FROM ai_reviews WHERE submission_id = ?');
$reviewStmt->execute([$id]);
$review = $reviewStmt->fetch();
$findings = $review ? json_decode($review['findings_json'], true) : [];

$pageTitle = 'Hasil Audit';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-4xl mx-auto px-6 py-10">
  <!-- Back -->
  <a href="<?= APP_URL ?>/<?= $user['role']==='siswa' ? 'dashboard.php' : 'company/talent.php' ?>" class="inline-flex items-center gap-2 text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors mb-6">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" x2="5" y1="12" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Kembali
  </a>

  <!-- Header -->
  <div class="flex items-start justify-between gap-4 flex-wrap animate-fade-up">
    <div>
      <p class="text-sm text-[var(--muted)]">Hasil Audit &middot; <?= e($submission['student_name']) ?></p>
      <h1 class="text-2xl md:text-3xl font-bold tracking-tight mt-1"><?= e($submission['task_title']) ?></h1>
    </div>
    <?php if ($user['role'] === 'siswa'): ?>
    <a href="<?= APP_URL ?>/mentor.php?submission_id=<?= $submission['id'] ?>" class="btn btn-ghost">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Tanya Agent Mentor
    </a>
    <?php endif; ?>
  </div>

  <?php
  $defStmt = $pdo->prepare('SELECT * FROM defense_sessions WHERE submission_id = ?');
  $defStmt->execute([$id]);
  $defenseSession = $defStmt->fetch();
  ?>
  <?php if ($defenseSession && $user['role'] === 'siswa'): ?>
    <div class="mt-6 surface p-5 rounded-2xl flex items-center justify-between gap-4 flex-wrap" style="border-color: var(--accent-100); background: var(--accent-50);">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl grid place-items-center shrink-0" style="background: white; color: var(--accent);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
        </div>
        <div>
          <p class="font-semibold text-sm text-[var(--ink)]">Sesi Pembelaan Project</p>
          <p class="text-xs text-[var(--muted)]">
            <?= $defenseSession['status'] === 'evaluated'
                ? 'Selesai · skor pemahaman ' . (int)$defenseSession['comprehension_score'] . '/100'
                : 'Wajib diselesaikan agar skor pemahamanmu ikut dihitung ke profil' ?>
          </p>
        </div>
      </div>
      <a href="<?= APP_URL ?>/defense.php?submission_id=<?= $id ?>" class="btn <?= $defenseSession['status'] === 'evaluated' ? 'btn-ghost' : 'btn-accent' ?> btn-sm">
        <?= $defenseSession['status'] === 'evaluated' ? 'Lihat Hasil' : 'Mulai Sesi' ?>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" x2="19" y1="12" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
  <?php endif; ?>

  <?php if (!$review): ?>
    <div class="mt-8 surface rounded-3xl p-12">
      <div class="empty-state">
        <div class="empty-state-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="animate-spin"><circle cx="12" cy="12" r="10" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg>
        </div>
        <p class="empty-state-title">Audit sedang diproses</p>
        <p class="empty-state-desc">Coba muat ulang beberapa saat lagi.</p>
      </div>
    </div>
  <?php else: ?>

  <!-- Score Overview -->
  <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6 stagger">
    <div class="surface spot-card p-8 flex items-center gap-6 lg:col-span-1 rounded-3xl">
      <div class="relative w-24 h-24 shrink-0">
        <svg class="score-ring w-24 h-24" data-score="<?= (int)$review['overall_score'] ?>" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0" stroke-width="8"/>
          <circle class="progress" cx="50" cy="50" r="42" fill="none" stroke="url(#submissionGradient)" stroke-width="8" stroke-linecap="round"/>
          <defs>
            <linearGradient id="submissionGradient" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" style="stop-color:#3b82f6"/>
              <stop offset="100%" style="stop-color:#1d4ed8"/>
            </linearGradient>
          </defs>
        </svg>
        <div class="absolute inset-0 grid place-items-center">
          <span class="text-2xl font-extrabold text-[var(--ink)]"><?= (int)$review['overall_score'] ?></span>
        </div>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Skor Keseluruhan</p>
        <p class="mt-1 text-sm text-[var(--muted)] leading-relaxed"><?= e($review['summary']) ?></p>
      </div>
    </div>

    <div class="lg:col-span-2 surface p-8 rounded-3xl grid grid-cols-3 gap-4">
      <div class="text-center p-4 rounded-2xl hover:bg-[var(--accent-50)] transition-colors">
        <div class="w-10 h-10 rounded-xl mx-auto mb-3 flex items-center justify-center" style="background: var(--accent-50);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        </div>
        <p class="text-2xl font-extrabold <?= score_color_class((int)$review['clean_code_score']) ?>"><?= (int)$review['clean_code_score'] ?></p>
        <p class="text-xs text-[var(--muted)] mt-1 font-medium"><?= e($rubric[0]['label']) ?></p>
      </div>
      <div class="text-center p-4 rounded-2xl hover:bg-[var(--success-50)] transition-colors">
        <div class="w-10 h-10 rounded-xl mx-auto mb-3 flex items-center justify-center" style="background: var(--success-50);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        </div>
        <p class="text-2xl font-extrabold <?= score_color_class((int)$review['security_score']) ?>"><?= (int)$review['security_score'] ?></p>
        <p class="text-xs text-[var(--muted)] mt-1 font-medium"><?= e($rubric[1]['label']) ?></p>
      </div>
      <div class="text-center p-4 rounded-2xl hover:bg-[var(--warning-50)] transition-colors">
        <div class="w-10 h-10 rounded-xl mx-auto mb-3 flex items-center justify-center" style="background: var(--warning-50);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
        </div>
        <p class="text-2xl font-extrabold <?= score_color_class((int)$review['efficiency_score']) ?>"><?= (int)$review['efficiency_score'] ?></p>
        <p class="text-xs text-[var(--muted)] mt-1 font-medium"><?= e($rubric[2]['label']) ?></p>
      </div>
    </div>
  </div>

  <!-- Findings -->
  <div class="mt-10">
    <h2 class="text-lg font-bold mb-5 flex items-center gap-2">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
      Temuan Agent Auditor
    </h2>
    <div class="space-y-3 stagger">
      <?php foreach ($findings as $f):
        $sevConfig = [
            'critical' => ['class' => 'badge-critical', 'label' => 'Kritis', 'icon' => '<circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/>'],
            'warning' => ['class' => 'badge-warning', 'label' => 'Perhatian', 'icon' => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/>'],
            'info' => ['class' => 'badge-info', 'label' => 'Info', 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>'],
        ];
        $sev = $sevConfig[$f['severity']] ?? $sevConfig['info'];
        $source = $f['source'] ?? 'ai-judged';
      ?>
      <div class="surface p-5 rounded-2xl flex items-start gap-4 transition-all duration-200 hover:shadow-md">
        <div class="shrink-0 mt-0.5">
          <span class="<?= $sev['class'] ?> badge">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><?= $sev['icon'] ?></svg>
            <?= $sev['label'] ?>
          </span>
        </div>
        <div class="flex-1">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="font-semibold text-sm text-[var(--ink)]"><?= e($f['title']) ?></p>
            <?php if ($source === 'static-verified'): ?>
              <span class="badge badge-success" title="Ditemukan lewat pemeriksaan pola deterministik, bukan opini AI">✓ Verified</span>
            <?php else: ?>
              <span class="badge badge-accent" title="Penilaian kualitatif dari Groq AI">AI Judgment</span>
            <?php endif; ?>
          </div>
          <p class="mt-1.5 text-sm text-[var(--muted)] leading-relaxed"><?= e($f['detail']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Hasil Kerja Siswa -->
  <div class="mt-10">
    <h2 class="text-lg font-bold mb-5 flex items-center gap-2">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--ink)" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
      <?= $submissionType === 'code' ? 'Kode yang Dikirim' : 'Hasil Kerja yang Dikirim' ?>
    </h2>

    <?php if ($submission['external_link']): ?>
      <a href="<?= e($submission['external_link']) ?>" target="_blank" rel="noopener" class="mb-4 flex items-center gap-3 p-4 rounded-2xl border border-accent-100 hover:shadow-md transition-all" style="background: var(--accent-50);">
        <div class="w-9 h-9 rounded-lg grid place-items-center shrink-0" style="background: white; color: var(--accent);">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        </div>
        <span class="text-sm font-medium text-accent-dark truncate"><?= e($submission['external_link']) ?></span>
      </a>
    <?php endif; ?>

    <?php if ($submission['file_path']): ?>
      <div class="mb-4">
        <?php if ($isImageFile): ?>
          <a href="<?= APP_URL ?>/<?= e($submission['file_path']) ?>" target="_blank" rel="noopener">
            <img src="<?= APP_URL ?>/<?= e($submission['file_path']) ?>" alt="File submission" class="rounded-2xl border border-[var(--border-light)] max-h-[500px] w-auto">
          </a>
        <?php else: ?>
          <a href="<?= APP_URL ?>/<?= e($submission['file_path']) ?>" target="_blank" rel="noopener" class="link-accent text-sm">Buka file lampiran &rarr;</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($submissionType === 'code'): ?>
      <pre class="code-block"><?= e($submission['code_content']) ?></pre>
    <?php else: ?>
      <div class="surface p-6 rounded-2xl">
        <p class="text-sm text-[var(--ink-light)] leading-relaxed whitespace-pre-line"><?= e($submission['code_content']) ?></p>
      </div>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
