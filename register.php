<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');

$errors = [];
$old = ['name' => '', 'email' => '', 'role' => 'siswa', 'sekolah' => '', 'jurusan' => '', 'company_name' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Sesi form kedaluwarsa, silakan coba lagi.';
    } else {
        $old['name'] = trim($_POST['name'] ?? '');
        $old['email'] = trim($_POST['email'] ?? '');
        $old['role'] = ($_POST['role'] ?? 'siswa') === 'mitra' ? 'mitra' : 'siswa';
        $old['sekolah'] = trim($_POST['sekolah'] ?? '');
        $old['jurusan'] = trim($_POST['jurusan'] ?? '');
        $old['company_name'] = trim($_POST['company_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($old['name'] === '') $errors[] = 'Nama lengkap wajib diisi.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
        if (strlen($password) < 8) $errors[] = 'Password minimal 8 karakter.';
        if ($password !== $passwordConfirm) $errors[] = 'Konfirmasi password tidak cocok.';
        if ($old['role'] === 'mitra' && $old['company_name'] === '') $errors[] = 'Nama perusahaan wajib diisi untuk akun Mitra.';

        if (empty($errors)) {
            $pdo = db();
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$old['email']]);
            if ($check->fetch()) {
                $errors[] = 'Email ini sudah terdaftar. Coba masuk, atau gunakan email lain.';
            } else {
                $pdo->beginTransaction();
                try {
                    $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, avatar_initial) VALUES (?,?,?,?,?)');
                    $ins->execute([$old['name'], $old['email'], password_hash($password, PASSWORD_DEFAULT), $old['role'], initials($old['name'])]);
                    $userId = (int) $pdo->lastInsertId();

                    if ($old['role'] === 'siswa') {
                        $pdo->prepare('INSERT INTO student_profiles (user_id, sekolah, jurusan) VALUES (?,?,?)')
                            ->execute([$userId, $old['sekolah'] ?: null, $old['jurusan'] ?: null]);
                        $pdo->prepare('INSERT INTO skill_profiles (user_id, badge) VALUES (?, \'Pemula\')')->execute([$userId]);
                    } else {
                        $pdo->prepare('INSERT INTO company_profiles (user_id, company_name) VALUES (?,?)')
                            ->execute([$userId, $old['company_name']]);
                    }

                    $pdo->commit();

                    $userRow = $pdo->prepare('SELECT id, name, email, role, avatar_initial, is_active, created_at, updated_at FROM users WHERE id = ?');
                    $userRow->execute([$userId]);
                    $_SESSION['user'] = $userRow->fetch();
                    log_activity($userId, 'register', "Role: {$old['role']}");

                    redirect('dashboard.php');
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $errors[] = 'Gagal membuat akun. Coba lagi.';
                }
            }
        }
    }
}

$pageTitle = 'Daftar Akun';
require __DIR__ . '/includes/header.php';
?>

<div class="min-h-[80vh] flex items-center justify-center px-6 py-10">
  <div class="w-full max-w-md mx-auto animate-fade-up">
    <div class="lg:hidden flex items-center gap-3 mb-8">
      <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold" style="background: var(--gradient-accent); box-shadow: 0 2px 8px rgba(37,99,235,0.3);">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
      </div>
      <span class="font-bold text-xl">SkillSync <span class="text-accent">AI</span></span>
    </div>

    <h1 class="text-2xl md:text-3xl font-bold tracking-tight">Buat akun baru</h1>
    <p class="mt-2 text-sm text-[#525252]">Gabung sebagai siswa untuk mulai mengasah kompetensi, atau sebagai mitra untuk menemukan talenta.</p>

    <?php if ($errors): ?>
      <div class="mt-5 p-4 rounded-xl border border-red-200 flex items-start gap-3" style="background: #fef2f2;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" class="shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
        <div class="text-sm text-red-700">
          <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <form method="POST" class="mt-6 space-y-4" id="registerForm">
      <?= csrf_field() ?>

      <div>
        <label>Daftar sebagai</label>
        <div class="grid grid-cols-2 gap-3 mt-1">
          <label class="role-option <?= $old['role'] === 'siswa' ? 'active' : '' ?>">
            <input type="radio" name="role" value="siswa" <?= $old['role'] === 'siswa' ? 'checked' : '' ?> class="sr-only" onchange="document.querySelectorAll('.role-option').forEach(e=>e.classList.remove('active'));this.closest('label').classList.add('active');document.getElementById('siswaFields').classList.remove('hidden');document.getElementById('mitraFields').classList.add('hidden');">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            Siswa
          </label>
          <label class="role-option <?= $old['role'] === 'mitra' ? 'active' : '' ?>">
            <input type="radio" name="role" value="mitra" <?= $old['role'] === 'mitra' ? 'checked' : '' ?> class="sr-only" onchange="document.querySelectorAll('.role-option').forEach(e=>e.classList.remove('active'));this.closest('label').classList.add('active');document.getElementById('mitraFields').classList.remove('hidden');document.getElementById('siswaFields').classList.add('hidden');">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M8 10h.01M16 10h.01M8 14h.01M16 14h.01"/></svg>
            Mitra Industri
          </label>
        </div>
      </div>

      <div>
        <label for="name">Nama Lengkap</label>
        <input type="text" id="name" name="name" required value="<?= e($old['name']) ?>" placeholder="Nama lengkapmu">
      </div>
      <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required value="<?= e($old['email']) ?>" placeholder="nama@email.com">
      </div>

      <div id="siswaFields" class="<?= $old['role'] === 'mitra' ? 'hidden' : '' ?> space-y-4">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="sekolah">Sekolah</label>
            <input type="text" id="sekolah" name="sekolah" value="<?= e($old['sekolah']) ?>" placeholder="SMKN 9 Bekasi">
          </div>
          <div>
            <label for="jurusan">Jurusan</label>
            <input type="text" id="jurusan" name="jurusan" value="<?= e($old['jurusan']) ?>" placeholder="RPL / TKJ / DKV">
          </div>
        </div>
      </div>

      <div id="mitraFields" class="<?= $old['role'] === 'mitra' ? '' : 'hidden' ?>">
        <label for="company_name">Nama Perusahaan</label>
        <input type="text" id="company_name" name="company_name" value="<?= e($old['company_name']) ?>" placeholder="PT Contoh Teknologi">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required placeholder="Min. 8 karakter">
        </div>
        <div>
          <label for="password_confirm">Konfirmasi</label>
          <input type="password" id="password_confirm" name="password_confirm" required placeholder="Ulangi password">
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-full py-3 mt-2">
        Buat Akun
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" x2="19" y1="12" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </button>
    </form>

    <p class="mt-6 text-center text-sm text-[#525252]">Sudah punya akun? <a href="<?= APP_URL ?>/login.php" class="link-accent">Masuk di sini</a></p>
  </div>
</div>

<style>
.role-option { display:flex; align-items:center; gap:8px; padding:0.75rem 1rem; border-radius: var(--radius-lg); border:1.5px solid var(--border); font-size:0.875rem; font-weight:600; color: var(--muted); cursor:pointer; transition: all var(--duration-fast) ease; }
.role-option:hover { border-color: var(--accent-100); background: var(--accent-50); }
.role-option.active { border-color: var(--accent); background: var(--accent-50); color: var(--accent-dark); }
</style>

<?php require __DIR__ . '/includes/footer.php'; ?>
