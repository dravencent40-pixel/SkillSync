<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = current_user();
$pdo = db();
$pageTitle = 'Studi Kasus';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'mitra') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $industry = trim($_POST['industry_context'] ?? '');
    $brief = trim($_POST['case_brief'] ?? '');
    $starter = $_POST['starter_code'] ?? '';
    $difficulty = in_array($_POST['difficulty'] ?? '', ['pemula','menengah','mahir']) ? $_POST['difficulty'] : 'pemula';

    if ($title === '' || $brief === '' || $categoryId === 0) {
        $errors[] = 'Judul, kategori, dan deskripsi studi kasus wajib diisi.';
    } else {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) . '-' . substr(uniqid(), -4);
        $pdo->prepare('INSERT INTO tasks (category_id, created_by, title, slug, industry_context, case_brief, starter_code, difficulty)
                        VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$categoryId, $user['id'], $title, $slug, $industry, $brief, $starter, $difficulty]);
        flash('success', 'Studi kasus baru berhasil diterbitkan oleh Agent Task Issuer.');
        redirect('tasks.php');
    }
}

$categories = $pdo->query('SELECT * FROM task_categories ORDER BY name')->fetchAll();

if ($user['role'] === 'siswa') {
    $tasks = $pdo->prepare(
        "SELECT t.*, c.name AS category_name,
                (SELECT COUNT(*) FROM submissions s WHERE s.task_id = t.id AND s.user_id = ?) AS done
         FROM tasks t JOIN task_categories c ON c.id = t.category_id
         WHERE t.is_active = 1 ORDER BY t.created_at DESC"
    );
    $tasks->execute([$user['id']]);
} else {
    $tasks = $pdo->prepare(
        "SELECT t.*, c.name AS category_name,
                (SELECT COUNT(*) FROM submissions s WHERE s.task_id = t.id) AS submission_count
         FROM tasks t JOIN task_categories c ON c.id = t.category_id
         ORDER BY t.created_at DESC"
    );
    $tasks->execute();
}
$tasks = $tasks->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<section class="max-w-7xl mx-auto px-6 py-10">
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 animate-fade-up">
    <div>
      <h1 class="text-3xl font-bold tracking-tight">Studi Kasus</h1>
      <p class="mt-1 text-sm text-[var(--muted)]"><?= $user['role']==='siswa' ? 'Diterbitkan oleh Agent Task Issuer & mitra industri.' : 'Kelola bank soal yang akan disajikan Agent Task Issuer.' ?></p>
    </div>
    <?php if ($user['role'] === 'mitra'): ?>
    <button onclick="document.getElementById('newTaskModal').classList.remove('hidden')" class="btn btn-primary shrink-0">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" x2="12" y1="5" y2="19"/><line x1="5" x2="19" y1="12" y2="12"/></svg>
       Terbitkan Studi Kasus
    </button>
    <?php endif; ?>
  </div>

  <?php if ($errors): ?>
    <div class="mt-6 p-4 rounded-xl border border-red-200 flex items-start gap-3 animate-fade-up" style="background: var(--danger-50);">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" class="shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
      <div class="text-sm text-red-700">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (empty($tasks)): ?>
    <div class="mt-10 surface rounded-3xl p-14">
      <div class="empty-state">
        <div class="empty-state-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <p class="empty-state-title">Belum ada studi kasus</p>
        <p class="empty-state-desc">Studi kasus akan muncul di sini setelah diterbitkan.</p>
      </div>
    </div>
  <?php else: ?>
  <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 stagger">
    <?php foreach ($tasks as $t): ?>
    <div class="surface surface-hover spot-card p-6 rounded-2xl flex flex-col group">
      <div class="flex items-center justify-between">
        <span class="badge badge-info"><?= e($t['category_name']) ?></span>
        <span class="flex items-center gap-1.5 text-[11px] font-medium text-[var(--muted)] capitalize">
          <span class="w-1.5 h-1.5 rounded-full <?= $t['difficulty']==='mahir'?'bg-red-400':($t['difficulty']==='menengah'?'bg-neutral-400':'bg-neutral-400') ?>"></span>
          <?= e($t['difficulty']) ?>
        </span>
      </div>
      <h3 class="mt-4 font-semibold text-[var(--ink)] leading-snug group-hover:text-[#0a0a0a] transition-colors"><?= e($t['title']) ?></h3>
      <p class="mt-2 text-xs text-[var(--muted)] line-clamp-3 leading-relaxed"><?= e(mb_substr($t['case_brief'], 0, 110)) ?>…</p>
      <div class="mt-5 pt-4 border-t border-[var(--border-light)] flex items-center justify-between">
        <span class="text-xs text-[var(--muted-light)] flex items-center gap-1.5">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <?= e($t['industry_context']) ?>
        </span>
        <?php if ($user['role'] === 'siswa'): ?>
          <a href="<?= APP_URL ?>/task.php?id=<?= $t['id'] ?>" class="link-accent text-sm">
            <?= $t['done'] > 0 ? 'Lihat ulang' : 'Kerjakan' ?> →
          </a>
        <?php else: ?>
          <span class="text-xs text-[var(--muted-light)]"><?= (int)$t['submission_count'] ?> submission</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<!-- Create Task Modal (Mitra) -->
<?php if ($user['role'] === 'mitra'): ?>
  <div id="newTaskModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(15,23,42,0.6);">
  <div class="bg-white rounded-3xl max-w-lg w-full p-8 max-h-[90vh] overflow-y-auto animate-scale-in" style="box-shadow: var(--shadow-xl);">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-bold text-[var(--ink)]">Terbitkan Studi Kasus</h2>
        <p class="text-xs text-[var(--muted)] mt-0.5">Isi detail studi kasus untuk siswa</p>
      </div>
      <button onclick="document.getElementById('newTaskModal').classList.add('hidden')" class="modal-close">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
      </button>
    </div>
    <form method="POST" class="space-y-4">
      <div>
        <label>Judul</label>
        <input type="text" name="title" required placeholder="Perbaiki Endpoint API yang Rentan XSS">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label>Kategori</label>
          <select name="category_id" required>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Tingkat Kesulitan</label>
          <select name="difficulty">
            <option value="pemula">Pemula</option>
            <option value="menengah">Menengah</option>
            <option value="mahir">Mahir</option>
          </select>
        </div>
      </div>
      <div>
        <label>Konteks Industri</label>
        <input type="text" name="industry_context" placeholder="Fintech, E-commerce, Logistik…">
      </div>
      <div>
        <label>Deskripsi Studi Kasus</label>
        <textarea name="case_brief" required rows="4" placeholder="Jelaskan permasalahan riil yang harus diselesaikan siswa…"></textarea>
      </div>
      <div>
        <label>Kode Awal <span class="text-[var(--muted-light)] font-normal">(opsional)</span></label>
        <textarea name="starter_code" rows="4" class="code-editor" style="background: var(--ink); color: #e2e8f0; font-size: 0.8125rem;" placeholder="<?php\n// kode bermasalah yang perlu diperbaiki siswa"></textarea>
      </div>
      <button type="submit" class="btn btn-dark w-full py-3">Terbitkan</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
