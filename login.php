<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Sesi form kedaluwarsa, silakan coba lagi.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            unset($user['password_hash']);
            $_SESSION['user'] = $user;
            log_activity($user['id'], 'login', null);
            $next = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);
            if ($next) {
                $basePath = parse_url(APP_URL, PHP_URL_PATH) ?? '';
                if ($basePath && str_starts_with($next, $basePath)) {
                    $next = substr($next, strlen($basePath));
                }
                $next = ltrim($next, '/');
            }
            redirect($next ?: 'dashboard.php');
        }
        $errors[] = 'Email atau password salah.';
    }
}

$pageTitle = 'Masuk';
require __DIR__ . '/includes/header.php';
?>

<div class="min-h-[80vh] flex items-center justify-center px-6 py-10">
  <div class="w-full max-w-5xl grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
    <div class="hidden lg:block animate-fade-up">
      <div class="p-10 rounded-3xl relative overflow-hidden" style="background: var(--gradient-dark);">
        <div class="blob" style="width: 250px; height: 250px; top: -80px; right: -50px; background: rgba(255,255,255,0.08);"></div>
        <div class="relative z-10">
          <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-8" style="background: rgba(255,255,255,0.1); box-shadow: 0 4px 16px rgba(0,0,0,0.3);">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
          </div>
          <h2 class="text-2xl font-bold text-white leading-tight">SkillSync <span class="text-neutral-400">AI</span></h2>
          <p class="mt-3 text-sm text-neutral-400 leading-relaxed max-w-[30ch]">Platform asesmen teknis berbasis AI untuk siswa SMK dan mitra industri.</p>
          <div class="mt-10 space-y-4">
            <div class="flex items-center gap-3 text-sm text-neutral-300">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(255,255,255,0.1);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
              </div>
              Automated code audit oleh AI
            </div>
            <div class="flex items-center gap-3 text-sm text-neutral-300">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(255,255,255,0.1);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 0 1-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
              </div>
              AI Mentor interaktif 24/7
            </div>
            <div class="flex items-center gap-3 text-sm text-neutral-300">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(255,255,255,0.1);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              </div>
              Profil kompetensi transparan
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="animate-fade-up" style="animation-delay: 0.1s;">
      <div class="w-full max-w-md mx-auto">
        <div class="lg:hidden flex items-center gap-3 mb-8">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold" style="background: var(--gradient-dark); box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
          </div>
          <span class="font-bold text-xl">SkillSync <span class="text-neutral-500">AI</span></span>
        </div>

        <h1 class="text-2xl md:text-3xl font-bold tracking-tight">Selamat datang kembali</h1>
        <p class="mt-2 text-sm text-[#525252]">Masuk untuk melanjutkan progres kamu.</p>

        <div class="mt-6 p-4 rounded-xl border border-accent-100" style="background: var(--accent-50);">
          <div class="flex items-center gap-2 mb-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            <span class="text-xs font-semibold">Akun Demo</span>
          </div>
          <div class="space-y-1 text-xs text-[#525252]">
            <p><span class="font-mono font-medium text-[#0a0a0a]">rafi@smkn9bekasi.sch.id</span> (Siswa)</p>
            <p><span class="font-mono font-medium text-[#0a0a0a]">admin@goodeva.tech</span> (Mitra)</p>
            <p>Password: <span class="font-mono font-medium text-[#0a0a0a]">password123</span></p>
          </div>
        </div>

        <?php if ($errors): ?>
          <div class="mt-5 p-4 rounded-xl border border-red-200 flex items-start gap-3" style="background: #fef2f2;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" class="shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
            <div class="text-sm text-red-700">
              <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <form method="POST" class="mt-6 space-y-4">
          <?= csrf_field() ?>
          <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="nama@email.com">
          </div>
          <div>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Masukkan password">
          </div>
          <button type="submit" class="btn btn-dark w-full py-3 mt-2">
            Masuk
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" x2="19" y1="12" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </button>
        </form>

        <p class="mt-6 text-center text-sm text-[#525252]">Belum punya akun? <a href="<?= APP_URL ?>/register.php" class="link-accent">Daftar di sini</a></p>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
