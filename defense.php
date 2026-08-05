<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/agents/DefenseAgent.php';
require_once __DIR__ . '/includes/agents/ProfileGeneratorAgent.php';
require_login();
$user = current_user();
$pdo = db();

$submissionId = (int) ($_GET['submission_id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT s.*, t.title AS task_title
     FROM submissions s JOIN tasks t ON t.id = s.task_id
     WHERE s.id = ?'
);
$stmt->execute([$submissionId]);
$submission = $stmt->fetch();

if (!$submission || ($user['role'] === 'siswa' && $submission['user_id'] != $user['id'])) {
    flash('error', 'Submission tidak ditemukan.');
    redirect('dashboard.php');
}

$sessionStmt = $pdo->prepare('SELECT * FROM defense_sessions WHERE submission_id = ?');
$sessionStmt->execute([$submissionId]);
$session = $sessionStmt->fetch();

if (!$session) {
    flash('error', 'Sesi pembelaan belum tersedia untuk submission ini.');
    redirect('submission.php?id=' . $submissionId);
}

$qStmt = $pdo->prepare('SELECT * FROM defense_questions WHERE session_id = ? ORDER BY order_index ASC');
$qStmt->execute([$session['id']]);
$questions = $qStmt->fetchAll();

$errors = [];

// --- Submit jawaban (hanya siswa pemilik, hanya jika masih pending) --------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'siswa' && $session['status'] === 'pending') {
    if (!csrf_verify()) {
        redirect('defense.php?submission_id=' . $submissionId);
    }

    $answers = $_POST['answers'] ?? [];
    $qa = [];
    foreach ($questions as $q) {
        $ans = trim($answers[$q['id']] ?? '');
        $qa[] = ['id' => $q['id'], 'question' => $q['question'], 'answer' => $ans];
    }

    if (count(array_filter($qa, fn($x) => $x['answer'] !== '')) === 0) {
        $errors[] = 'Jawab minimal satu pertanyaan sebelum submit.';
    } else {
        $pdo->prepare('UPDATE defense_sessions SET status=\'answered\', answered_at=NOW() WHERE id = ?')->execute([$session['id']]);
        $updAns = $pdo->prepare('UPDATE defense_questions SET answer = ? WHERE id = ?');
        foreach ($qa as $item) {
            $updAns->execute([$item['answer'], $item['id']]);
        }

        $result = (new DefenseAgent())->evaluateAnswers($submission['code_content'], $qa);

        $updScore = $pdo->prepare('UPDATE defense_questions SET answer_score = ?, answer_feedback = ? WHERE id = ?');
        foreach ($result['per_question'] as $i => $pq) {
            if (isset($qa[$i])) {
                $updScore->execute([(int) ($pq['score'] ?? 0), $pq['feedback'] ?? '', $qa[$i]['id']]);
            }
        }

        $pdo->prepare('UPDATE defense_sessions SET status=\'evaluated\', comprehension_score=?, feedback=?, ai_assisted=?, evaluated_at=NOW() WHERE id = ?')
            ->execute([$result['comprehension_score'], $result['feedback'], $result['ai_assisted'] ? 1 : 0, $session['id']]);

        (new ProfileGeneratorAgent())->regenerate($user['id']);
        log_activity($user['id'], 'defense_completed', "\"{$submission['task_title']}\" · skor pemahaman {$result['comprehension_score']}/100");

        redirect('defense.php?submission_id=' . $submissionId);
    }
}

// Reload state terbaru setelah kemungkinan update di atas
$sessionStmt->execute([$submissionId]);
$session = $sessionStmt->fetch();
$qStmt->execute([$session['id']]);
$questions = $qStmt->fetchAll();

$pageTitle = 'Sesi Pembelaan Project';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-3xl mx-auto px-6 py-10">
  <a href="<?= APP_URL ?>/submission.php?id=<?= $submissionId ?>" class="inline-flex items-center gap-2 text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors mb-6">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" x2="5" y1="12" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Kembali ke Hasil Audit
  </a>

  <div class="flex items-start gap-4 animate-fade-up">
    <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0" style="background: var(--accent-50); color: var(--accent);">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
    </div>
    <div>
      <p class="text-sm text-[var(--muted)]">Agent Defense &middot; <?= e($submission['task_title']) ?></p>
      <h1 class="text-2xl md:text-3xl font-bold tracking-tight mt-1">Sesi Pembelaan Project</h1>
      <p class="mt-2 text-sm text-[var(--muted)] leading-relaxed max-w-[60ch]">
        Jawab pertanyaan berikut dengan kata-katamu sendiri. Ini bukan ujian hafalan — tujuannya memverifikasi kamu
        benar-benar memahami keputusan di project yang kamu submit sendiri, bukan sekadar hasil generate tanpa dipahami.
      </p>
    </div>
  </div>

  <?php if ($errors): ?>
    <div class="mt-6 p-4 rounded-2xl bg-[var(--danger-50)] border border-[#f3d6d2] text-[var(--danger)] text-sm"><?= e($errors[0]) ?></div>
  <?php endif; ?>

  <?php if ($session['status'] === 'evaluated'): ?>
    <!-- ==================== HASIL EVALUASI ==================== -->
    <div class="mt-8 surface spot-card p-8 flex items-center gap-6">
      <div class="relative w-24 h-24 shrink-0">
        <svg class="score-ring w-24 h-24" data-score="<?= (int)$session['comprehension_score'] ?>" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="42" fill="none" stroke="var(--border)" stroke-width="8"/>
          <circle class="progress" cx="50" cy="50" r="42" fill="none" stroke="var(--accent)" stroke-width="8" stroke-linecap="round"/>
        </svg>
        <div class="absolute inset-0 grid place-items-center">
          <span class="num text-2xl font-extrabold text-[var(--ink)]"><?= (int)$session['comprehension_score'] ?></span>
        </div>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)]">Skor Pemahaman</p>
        <p class="mt-1 text-sm text-[var(--muted)] leading-relaxed"><?= e($session['feedback']) ?></p>
        <?php if (!$session['ai_assisted']): ?>
          <span class="badge badge-warning mt-2">Dinilai mode heuristik — sambungkan Groq API untuk penilaian penuh</span>
        <?php else: ?>
          <span class="badge badge-accent mt-2">Dinilai oleh Groq AI</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="mt-8 space-y-4 stagger">
      <?php foreach ($questions as $q): ?>
        <div class="surface p-6">
          <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)] mb-1">Pertanyaan</p>
          <p class="font-semibold text-[var(--ink)]"><?= e($q['question']) ?></p>
          <p class="text-xs font-semibold uppercase tracking-wider text-[var(--muted-light)] mt-4 mb-1">Jawabanmu</p>
          <p class="text-sm text-[var(--muted)] leading-relaxed"><?= $q['answer'] !== '' && $q['answer'] !== null ? e($q['answer']) : '(tidak dijawab)' ?></p>
          <div class="mt-4 pt-4 border-t border-[var(--border-light)] flex items-start gap-3">
            <span class="badge <?= $q['answer_score'] >= 70 ? 'badge-success' : ($q['answer_score'] >= 40 ? 'badge-warning' : 'badge-critical') ?> shrink-0"><?= (int)$q['answer_score'] ?>/100</span>
            <p class="text-sm text-[var(--muted)]"><?= e($q['answer_feedback']) ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?php elseif ($user['role'] !== 'siswa'): ?>
    <div class="mt-8 surface p-12">
      <div class="empty-state">
        <div class="empty-state-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <p class="empty-state-title">Menunggu siswa menyelesaikan sesi</p>
        <p class="empty-state-desc">Hasil pembelaan akan tampil di sini setelah siswa menjawab dan dinilai AI.</p>
      </div>
    </div>

  <?php else: ?>
    <!-- ==================== FORM PERTANYAAN ==================== -->
    <form method="POST" class="mt-8 space-y-5 stagger">
      <?= csrf_field() ?>
      <?php foreach ($questions as $i => $q): ?>
        <div class="surface p-6">
          <div class="flex items-center gap-2 mb-3">
            <span class="w-6 h-6 rounded-full grid place-items-center text-xs font-bold text-white" style="background: var(--gradient-accent);"><?= $i + 1 ?></span>
            <p class="font-semibold text-[var(--ink)]"><?= e($q['question']) ?></p>
          </div>
          <textarea name="answers[<?= $q['id'] ?>]" rows="3" placeholder="Tulis jawabanmu di sini..."></textarea>
        </div>
      <?php endforeach; ?>

      <button type="submit" class="btn btn-primary btn-lg w-full sm:w-auto">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
        Submit Jawaban
      </button>
    </form>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
