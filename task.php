<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/agents/ReviewerAuditorAgent.php';
require_once __DIR__ . '/includes/agents/ProfileGeneratorAgent.php';
require_once __DIR__ . '/includes/agents/DefenseAgent.php';
require_login();
$user = current_user();
$pdo = db();

$taskId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT t.*, c.name AS category_name, c.submission_type, c.rubric_criteria FROM tasks t JOIN task_categories c ON c.id = t.category_id WHERE t.id = ?');
$stmt->execute([$taskId]);
$task = $stmt->fetch();
if (!$task) { flash('error', 'Studi kasus tidak ditemukan.'); redirect('tasks.php'); }

$submissionType = $task['submission_type'] ?: 'code';
$rubric = task_rubric($task);
$typeConfig = submission_type_config($submissionType);

$pageTitle = $task['title'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'siswa') {
    if (!csrf_verify()) {
        redirect('task.php?id=' . $taskId);
    }
    $content = $_POST['code_content'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $externalLink = trim($_POST['external_link'] ?? '');

    if (trim($content) === '') {
        $errors[] = $submissionType === 'code' ? 'Kode tidak boleh kosong.' : 'Penjelasan tidak boleh kosong.';
    }

    // --- Upload file opsional (screenshot desain, topologi jaringan, dll) ---
    $filePath = null;
    $fileNameForAi = null;
    if (!empty($_FILES['submission_file']['name']) && empty($errors)) {
        $file = $_FILES['submission_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Gagal mengunggah file. Coba lagi.';
        } else {
            $allowed = ['image/png', 'image/jpeg', 'image/webp', 'application/pdf'];
            $mime = function_exists('finfo_open')
                ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name'])
                : ($file['type'] ?? null);
            if (!in_array($mime, $allowed, true)) {
                $errors[] = 'Format file harus PNG/JPG/WEBP/PDF.';
            } elseif ($file['size'] > 8 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 8MB.';
            } else {
                $uploadsDir = __DIR__ . '/uploads/submissions';
                if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $basename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadsDir . '/' . $basename)) {
                    $filePath = 'uploads/submissions/' . $basename;
                    $fileNameForAi = $file['name'];
                } else {
                    $errors[] = 'Gagal menyimpan file. Periksa izin direktori.';
                }
            }
        }
    }

    if (empty($errors)) {
        $ins = $pdo->prepare('INSERT INTO submissions (task_id, user_id, language, code_content, file_path, external_link, notes, status) VALUES (?,?,?,?,?,?,?,\'submitted\')');
        $ins->execute([$taskId, $user['id'], $submissionType, $content, $filePath, $externalLink ?: null, $notes]);
        $submissionId = (int) $pdo->lastInsertId();

        $review = (new ReviewerAuditorAgent())->review($content, $task['case_brief'], $rubric, $submissionType, $externalLink ?: null, $fileNameForAi);
        $pdo->prepare(
            'INSERT INTO ai_reviews (submission_id, clean_code_score, security_score, efficiency_score, overall_score, summary, findings_json)
             VALUES (?,?,?,?,?,?,?)'
        )->execute([
            $submissionId, $review['clean_code_score'], $review['security_score'], $review['efficiency_score'],
            $review['overall_score'], $review['summary'], json_encode($review['findings'], JSON_UNESCAPED_UNICODE),
        ]);
        $pdo->prepare("UPDATE submissions SET status='reviewed' WHERE id = ?")->execute([$submissionId]);

        $mode = $review['ai_assisted'] ? 'Groq AI' : 'heuristik lokal';
        log_activity($user['id'], 'submission_reviewed', "\"{$task['title']}\" · skor {$review['overall_score']}/100 · mode: {$mode}");

        // --- Agent Defense: buat sesi pembelaan otomatis (anti-cheat) -----
        // Siswa wajib menjawab pertanyaan yang merujuk hasil kerja/temuan
        // spesifiknya sendiri sebelum skor pemahaman ikut membentuk profil.
        $defenseAgent = new DefenseAgent();
        $dq = $defenseAgent->generateQuestions($content, $task['case_brief'], $review['findings']);
        $sessionIns = $pdo->prepare('INSERT INTO defense_sessions (submission_id, status, ai_assisted) VALUES (?, \'pending\', ?)');
        $sessionIns->execute([$submissionId, $dq['ai_assisted'] ? 1 : 0]);
        $sessionId = (int) $pdo->lastInsertId();
        $qIns = $pdo->prepare('INSERT INTO defense_questions (session_id, order_index, question) VALUES (?,?,?)');
        foreach ($dq['questions'] as $i => $q) {
            $qIns->execute([$sessionId, $i, $q]);
        }

        (new ProfileGeneratorAgent())->regenerate($user['id']);

        redirect('submission.php?id=' . $submissionId);
    }
}

$mySubmission = null;
if ($user['role'] === 'siswa') {
    $s = $pdo->prepare('SELECT * FROM submissions WHERE task_id = ? AND user_id = ? ORDER BY submitted_at DESC LIMIT 1');
    $s->execute([$taskId, $user['id']]);
    $mySubmission = $s->fetch();
}

require __DIR__ . '/includes/header.php';
?>

<section class="max-w-5xl mx-auto px-6 py-10">
  <!-- Back link -->
  <a href="<?= APP_URL ?>/tasks.php" class="inline-flex items-center gap-2 text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors mb-6">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="5" y1="12" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Kembali ke Studi Kasus
  </a>

  <!-- Task Header -->
  <div class="animate-fade-up">
    <div class="flex items-center gap-2">
      <span class="badge badge-accent"><?= e($task['category_name']) ?></span>
      <span class="flex items-center gap-1.5 text-[11px] font-medium text-[var(--muted)] capitalize">
        <span class="w-1.5 h-1.5 rounded-full <?= $task['difficulty']==='mahir' ? 'bg-[var(--danger)]' : ($task['difficulty']==='menengah' ? 'bg-[var(--warning)]' : 'bg-[var(--accent)]') ?>"></span>
        <?= e($task['difficulty']) ?>
      </span>
    </div>
    <h1 class="mt-3 text-2xl md:text-3xl font-bold tracking-tight"><?= e($task['title']) ?></h1>
    <p class="mt-1 text-sm text-[var(--muted)]">Konteks industri: <?= e($task['industry_context'] ?: '-') ?></p>
  </div>

  <!-- Brief Card -->
  <div class="mt-8 surface p-8 animate-fade-up" style="animation-delay: 0.1s;">
    <div class="flex items-center gap-2 mb-4">
      <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)] border border-[var(--accent-100)]">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Brief dari Agent Task Issuer</p>
    </div>
    <p class="text-sm text-[var(--ink-light)] leading-relaxed whitespace-pre-line"><?= e($task['case_brief']) ?></p>

    <?php if ($task['starter_code']): ?>
    <div class="mt-6">
      <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)] mb-3 flex items-center gap-2">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        Kode Awal
      </p>
      <pre class="code-block"><?= e($task['starter_code']) ?></pre>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($user['role'] === 'siswa'): ?>
    <?php if ($mySubmission): ?>
      <div class="mt-6 p-4 rounded-2xl border border-[var(--border)] bg-[var(--paper-soft)] flex items-center justify-between animate-fade-up">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)]">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <span class="text-sm text-[var(--ink-light)]">Kamu sudah pernah mengirim solusi untuk studi kasus ini.</span>
        </div>
        <a href="<?= APP_URL ?>/submission.php?id=<?= $mySubmission['id'] ?>" class="link-accent text-sm shrink-0">Lihat hasil &rarr;</a>
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="mt-6 p-4 rounded-xl border border-[#f3d6d2] bg-[var(--danger-50)] flex items-start gap-3">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" class="shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
        <div class="text-sm text-[var(--danger)]">
          <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Submission Form -->
    <form method="POST" enctype="multipart/form-data" class="mt-6 surface p-8" id="submitForm">
      <?= csrf_field() ?>
      <div class="flex items-center gap-2 mb-4">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-[var(--accent-50)] text-[var(--accent)] border border-[var(--accent-100)]">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        </div>
        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Kirim Solusi Kamu &middot; <?= e($typeConfig['label']) ?></p>
      </div>

      <?php if ($submissionType === 'code'): ?>
        <textarea name="code_content" rows="14" required class="code-editor w-full" placeholder="Tempel kode PHP kamu di sini..."><?= e($task['starter_code'] ?? '') ?></textarea>
      <?php else: ?>
        <label><?= e($typeConfig['field_label']) ?></label>
        <textarea name="code_content" rows="8" required placeholder="Jelaskan pendekatan, keputusan desain/konfigurasi, dan alasannya secara detail..."></textarea>

        <?php if ($typeConfig['accepts_link']): ?>
        <div class="mt-4">
          <label>Link Eksternal <span class="text-[var(--muted-light)] font-normal">(mis. link Figma — opsional tapi sangat disarankan)</span></label>
          <input type="url" name="external_link" placeholder="https://figma.com/file/...">
        </div>
        <?php endif; ?>

        <?php if ($typeConfig['accepts_file']): ?>
        <div class="mt-4">
          <label>Unggah File <span class="text-[var(--muted-light)] font-normal">(screenshot/topologi/dokumen — PNG/JPG/PDF, maks 8MB, opsional tapi sangat disarankan)</span></label>
          <input type="file" name="submission_file" accept=".png,.jpg,.jpeg,.webp,.pdf" class="file-input-custom">
        </div>
        <?php endif; ?>
      <?php endif; ?>

      <div class="mt-4">
        <label>Catatan untuk reviewer <span class="text-[var(--muted-light)] font-normal">(opsional)</span></label>
        <textarea name="notes" rows="2" placeholder="Jelaskan pendekatan yang kamu ambil…"></textarea>
      </div>
      <button type="submit" id="submitBtn" class="btn btn-primary mt-5 px-8">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" x2="11" y1="2" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        Kirim untuk Diaudit Agent Reviewer
      </button>
    </form>
    <script>
      document.getElementById('submitForm').addEventListener('submit', function () {
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="animate-spin"><circle cx="12" cy="12" r="10" stroke-dasharray="31.4" stroke-dashoffset="10"/></svg> Agent Reviewer sedang mengaudit kode kamu…';
        btn.classList.add('opacity-70');
      });
    </script>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
