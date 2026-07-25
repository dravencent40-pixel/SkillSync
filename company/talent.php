<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$metaFile = __DIR__ . '/../uploads/cvs/metadata.json';
$talents = [];
if (file_exists($metaFile)) {
  $talents = array_reverse(json_decode(file_get_contents($metaFile), true) ?: []);
}

$pageTitle = 'Talent Pool';
require __DIR__ . '/../includes/header.php';
?>

<section class="max-w-7xl mx-auto px-6 py-10">
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 animate-fade-up">
    <div>
      <h1 class="text-3xl font-bold tracking-tight">Talent Pool</h1>
      <p class="mt-1 text-sm text-[var(--muted)]">Kumpulan CV yang diunggah pengguna. Temukan talenta terbaik untuk tim kamu.</p>
    </div>
    <a href="<?= APP_URL ?>/upload_cv.php" class="btn btn-primary shrink-0">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
      Unggah CV
    </a>
  </div>

  <?php if (empty($talents)): ?>
    <div class="mt-10 surface rounded-3xl p-14">
      <div class="empty-state">
        <div class="empty-state-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <p class="empty-state-title">Belum ada CV</p>
        <p class="empty-state-desc">CV akan muncul di sini setelah diunggah pengguna.</p>
      </div>
    </div>
  <?php else: ?>
  <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 stagger">
    <?php foreach ($talents as $t): ?>
    <div class="cv-card group">
      <div class="flex items-start gap-3">
        <?php if (!empty($t['thumb']) && file_exists(__DIR__ . '/../' . $t['thumb'])): ?>
          <img src="<?= APP_URL ?>/<?= ltrim($t['thumb'], '/') ?>" alt="thumb" class="w-14 h-14 rounded-xl object-cover border border-[var(--border-light)]" />
        <?php else: ?>
          <div class="avatar avatar-lg" style="background: var(--gradient-dark);"><?= e(initials($t['name'])) ?></div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="font-semibold text-[var(--ink)] truncate group-hover:text-[#0a0a0a] transition-colors"><?= e($t['name']) ?></p>
              <p class="text-xs text-[var(--muted)] truncate"><?= e($t['original'] ?? '') ?></p>
            </div>
            <span class="file-badge shrink-0"><?= strtoupper(pathinfo($t['original'] ?? ($t['filename'] ?? ''), PATHINFO_EXTENSION) ?: 'PDF') ?></span>
          </div>
          <div class="mt-3 flex items-center justify-between">
            <p class="text-[11px] text-[var(--muted-light)]"><?= date('d M Y', strtotime($t['uploaded_at'])) ?></p>
            <div class="flex items-center gap-3">
              <a href="#" data-preview="<?= APP_URL ?>/uploads/cvs/<?= rawurlencode($t['filename']) ?>" data-meta="<?= e($t['name']) ?> — <?= e($t['original']) ?>" class="text-xs font-semibold text-[#0a0a0a] hover:text-[#525252] transition-colors">Pratinjau</a>
              <a href="<?= APP_URL ?>/uploads/cvs/<?= rawurlencode($t['filename']) ?>" download class="text-xs font-semibold text-[var(--muted)] hover:text-[var(--ink)] transition-colors">Unduh</a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
