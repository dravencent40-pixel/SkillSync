<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
require_role('siswa');
$user = current_user();
$pdo = db();

$uploadsDir = __DIR__ . '/uploads/cvs';
if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        redirect('upload_cv.php');
    }

    if (empty($_FILES['cv_file']['name'])) {
        $errors[] = 'Pilih file CV terlebih dahulu.';
    } else {
        $file = $_FILES['cv_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Gagal mengunggah file. Coba lagi.';
        } else {
            $mime = function_exists('finfo_open')
                ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name'])
                : ($file['type'] ?? null);
            if (!in_array($mime, ['application/pdf'], true)) {
                $errors[] = 'Format file harus PDF.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 5MB.';
            }
        }
    }

    if (empty($errors)) {
        $existing = $pdo->prepare('SELECT cv_path FROM student_profiles WHERE user_id = ?');
        $existing->execute([$user['id']]);
        $old = $existing->fetchColumn();
        if ($old && file_exists(__DIR__ . '/' . $old)) {
            @unlink(__DIR__ . '/' . $old);
        }

        $basename = 'cv_' . $user['id'] . '_' . time() . '.pdf';
        if (move_uploaded_file($file['tmp_name'], $uploadsDir . '/' . $basename)) {
            $relPath = 'uploads/cvs/' . $basename;
            $pdo->prepare('UPDATE student_profiles SET cv_path = ?, cv_original_name = ?, cv_uploaded_at = NOW() WHERE user_id = ?')
                ->execute([$relPath, $file['name'], $user['id']]);
            log_activity($user['id'], 'cv_uploaded', $file['name']);
            flash('success', 'CV berhasil diunggah!');
            redirect('upload_cv.php');
        } else {
            $errors[] = 'Gagal menyimpan file. Periksa izin direktori uploads/cvs.';
        }
    }
}

$profileStmt = $pdo->prepare('SELECT cv_path, cv_original_name, cv_uploaded_at FROM student_profiles WHERE user_id = ?');
$profileStmt->execute([$user['id']]);
$profile = $profileStmt->fetch();

$pageTitle = 'Unggah CV';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-2xl mx-auto px-6 py-10">
  <div class="text-center mb-8 animate-fade-up">
    <div class="empty-state-icon mx-auto" style="background: var(--accent-50); color: var(--accent);">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
    </div>
    <h1 class="text-2xl md:text-3xl font-bold tracking-tight mt-4">Unggah CV Kamu</h1>
    <p class="mt-2 text-sm text-[var(--muted)] max-w-[50ch] mx-auto">CV ini akan ditautkan ke profil kompetensimu dan bisa dilihat oleh mitra industri di Talent Pool bersama skor studi kasus yang sudah kamu kerjakan.</p>
  </div>

  <?php if ($errors): ?>
    <div class="mb-6 p-4 rounded-xl border border-[#f3d6d2] flex items-start gap-3" style="background: var(--danger-50);">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" class="shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
      <div class="text-sm" style="color: var(--danger);"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
    </div>
  <?php endif; ?>

  <?php if ($profile && $profile['cv_path']): ?>
    <div class="surface p-6 mb-6 flex items-center justify-between gap-4 flex-wrap" style="border-color: var(--accent-100); background: var(--accent-50);">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl grid place-items-center shrink-0" style="background: white; color: var(--accent); box-shadow: var(--shadow-sm);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div>
          <p class="font-semibold text-sm text-[var(--ink)]"><?= e($profile['cv_original_name']) ?></p>
          <p class="text-xs text-[var(--muted)]">Diunggah <?= time_ago($profile['cv_uploaded_at']) ?></p>
        </div>
      </div>
      <a href="<?= APP_URL ?>/view_cv.php?file=<?= urlencode(basename($profile['cv_path'])) ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">Lihat CV</a>
    </div>

    <div class="mb-6">
      <iframe src="<?= APP_URL ?>/view_cv.php?file=<?= urlencode(basename($profile['cv_path'])) ?>" style="width:100%;height:500px;border:0;" class="rounded-2xl border border-[var(--border-light)]" title="Pratinjau CV"></iframe>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="surface p-8">
    <?= csrf_field() ?>
    <label>Unggah <?= $profile && $profile['cv_path'] ? 'ulang' : '' ?> CV (PDF, maks 5MB)</label>
    <input type="file" name="cv_file" accept="application/pdf" required class="file-input-custom">
    <button type="submit" class="btn btn-primary w-full py-3 mt-5">
      <?= $profile && $profile['cv_path'] ? 'Perbarui CV' : 'Unggah CV' ?>
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
    </button>
  </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
