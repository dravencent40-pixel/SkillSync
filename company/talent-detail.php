<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('mitra');
$user = current_user();
$pdo = db();

$studentId = (int) ($_GET['id'] ?? 0);

$companyStmt = $pdo->prepare('SELECT id FROM company_profiles WHERE user_id = ?');
$companyStmt->execute([$user['id']]);
$companyId = $companyStmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $companyId) {
    if (!csrf_verify()) {
        redirect('company/talent-detail.php?id=' . $studentId);
    }
    $status = in_array($_POST['status'] ?? '', ['disimpan','dihubungi','interview','magang']) ? $_POST['status'] : 'disimpan';
    $note = trim($_POST['note'] ?? '');
    $pdo->prepare(
        'INSERT INTO recommendations (company_id, user_id, status, note) VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE status = VALUES(status), note = VALUES(note)'
    )->execute([$companyId, $studentId, $status, $note]);
    flash('success', 'Status talenta berhasil diperbarui.');
    redirect('company/talent-detail.php?id=' . $studentId);
}

$stmt = $pdo->prepare(
    'SELECT u.name, u.email, sp.*, s.jurusan, s.kelas, s.sekolah, s.bio, s.github_url
     FROM users u
     JOIN skill_profiles sp ON sp.user_id = u.id
     LEFT JOIN student_profiles s ON s.user_id = u.id
     WHERE u.id = ?'
);
$stmt->execute([$studentId]);
$talent = $stmt->fetch();
if (!$talent) { flash('error', 'Talenta tidak ditemukan.'); redirect('company/talent.php'); }

$historyStmt = $pdo->prepare(
    'SELECT s.id, t.title, r.overall_score, s.submitted_at
     FROM submissions s JOIN tasks t ON t.id = s.task_id
     LEFT JOIN ai_reviews r ON r.submission_id = s.id
     WHERE s.user_id = ? ORDER BY s.submitted_at DESC'
);
$historyStmt->execute([$studentId]);
$history = $historyStmt->fetchAll();

$tracksStmt = $pdo->prepare(
    'SELECT spt.*, c.name AS category_name, c.rubric_criteria
     FROM skill_profile_tracks spt
     JOIN task_categories c ON c.id = spt.category_id
     WHERE spt.user_id = ? AND spt.tasks_completed > 0
     ORDER BY spt.tasks_completed DESC, spt.overall_score DESC'
);
$tracksStmt->execute([$studentId]);
$tracks = $tracksStmt->fetchAll();

$recStmt = $pdo->prepare('SELECT * FROM recommendations WHERE company_id = ? AND user_id = ?');
$recStmt->execute([$companyId, $studentId]);
$rec = $recStmt->fetch();

$pageTitle = $talent['name'];
require __DIR__ . '/../includes/header.php';
?>

<section class="max-w-4xl mx-auto px-6 py-10">
  <!-- Back -->
  <a href="<?= APP_URL ?>/company/talent.php" class="inline-flex items-center gap-2 text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors mb-6">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" x2="5" y1="12" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Kembali ke Talent Pool
  </a>

  <!-- Profile Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 animate-fade-up">
    <div class="flex items-center gap-5">
      <div class="avatar avatar-xl" style="background: var(--gradient-dark); box-shadow: 0 4px 16px rgba(0,0,0,0.2);">
        <?= e(initials($talent['name'])) ?>
      </div>
      <div>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tight"><?= e($talent['name']) ?></h1>
        <p class="text-sm text-[var(--muted)] mt-1"><?= e($talent['jurusan'] ?: '-') ?> &middot; <?= e($talent['sekolah'] ?: 'SMKN 9 Bekasi') ?></p>
      </div>
    </div>
    <span class="badge badge-info shrink-0"><?= e($talent['badge']) ?></span>
  </div>

  <!-- Score Overview -->
  <div class="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-6 stagger">
    <div class="surface spot-card p-8 flex items-center gap-6 lg:col-span-1 rounded-3xl">
      <div class="relative w-24 h-24 shrink-0">
        <svg class="score-ring w-24 h-24" data-score="<?= (int)$talent['overall_score'] ?>" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0" stroke-width="8"/>
          <circle class="progress" cx="50" cy="50" r="42" fill="none" stroke="url(#talentGradient)" stroke-width="8" stroke-linecap="round"/>
          <defs>
            <linearGradient id="talentGradient" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" style="stop-color:#0a0a0a"/>
              <stop offset="100%" style="stop-color:#525252"/>
            </linearGradient>
          </defs>
        </svg>
        <div class="absolute inset-0 grid place-items-center">
          <span class="text-xl font-extrabold text-[var(--ink)]"><?= (int)$talent['overall_score'] ?></span>
        </div>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Skor Keseluruhan</p>
        <p class="text-xs text-[var(--muted)] mt-1"><?= (int)$talent['tasks_completed'] ?> studi kasus diselesaikan</p>
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

  <!-- Agent Profile Generator — Narrative Summary -->
  <?php if (!empty($talent['narrative'])): ?>
  <div class="mt-8 surface p-8 rounded-3xl" style="background: var(--gradient-dark); color: white; border: none;">
    <div class="flex items-center gap-2 mb-3">
      <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(255,255,255,0.1);">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </div>
      <p class="text-xs font-semibold uppercase tracking-wider text-neutral-400">Ringkasan Agent Profile Generator</p>
    </div>
    <p class="text-sm text-neutral-200 leading-relaxed"><?= nl2br(e($talent['narrative'])) ?></p>
  </div>
  <?php endif; ?>

  <!-- Recruitment Status -->
  <div class="mt-8 surface rounded-3xl p-8">
    <div class="flex items-center gap-2 mb-4">
      <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: #f5f5f5; color: #0a0a0a;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Status Rekrutmen</p>
    </div>
    <form method="POST" class="flex flex-col sm:flex-row gap-4">
      <?= csrf_field() ?>
      <select name="status" class="flex-1">
        <?php foreach (['disimpan'=>'Disimpan','dihubungi'=>'Dihubungi','interview'=>'Interview','magang'=>'Diterima Magang'] as $val=>$label): ?>
          <option value="<?= $val ?>" <?= ($rec['status'] ?? '')===$val?'selected':'' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="note" value="<?= e($rec['note'] ?? '') ?>" placeholder="Catatan internal (opsional)" class="flex-1">
      <button type="submit" class="btn btn-dark shrink-0">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        Simpan
      </button>
    </form>
  </div>

  <!-- History -->
  <div class="mt-10">
    <h2 class="text-lg font-bold mb-5">Riwayat Studi Kasus</h2>
    <?php if (empty($history)): ?>
      <div class="surface rounded-3xl p-12">
        <div class="empty-state">
          <div class="empty-state-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <p class="empty-state-title">Belum ada riwayat</p>
          <p class="empty-state-desc">Talenta ini belum menyelesaikan studi kasus.</p>
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

<?php require __DIR__ . '/../includes/footer.php'; ?>
