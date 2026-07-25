<?php
$user = current_user();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' · ' : '' ?>SkillSync AI</title>
<meta name="description" content="Platform asesmen teknis berbasis AI untuk siswa SMK dan mitra industri.">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<script>
  tailwind.config = { theme: { extend: {
    colors: {
      ink: { DEFAULT: '#0a0a0a', light: '#171717' },
      muted: { DEFAULT: '#525252', light: '#737373' },
    },
    fontFamily: { sans: ['Outfit','sans-serif'], mono: ['JetBrains Mono','monospace'] },
    borderRadius: { '3xl': '2rem' },
  } } };
</script>
</head>
<body class="min-h-[100dvh] bg-[#fafafa] text-[#0a0a0a] antialiased">

<header id="mainHeader" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" style="background: rgba(250,250,250,0.95); border-bottom: 1px solid rgba(229,229,229,0.6);">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
    <a href="<?= APP_URL ?>/index.php" class="flex items-center gap-3 shrink-0 group">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-sm transition-transform duration-300 group-hover:scale-110" style="background: var(--gradient-dark); box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
      </div>
      <span class="font-bold tracking-tight text-lg hidden sm:block">SkillSync <span class="text-neutral-500">AI</span></span>
    </a>

    <?php $__aiMode = ai_mode(); ?>
    <span class="hidden lg:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium border" style="<?= $__aiMode['active'] ? 'background:#f5f5f5;color:#171717;border-color:#e5e5e5;' : 'background:#fffbeb;color:#92400e;border-color:#fde68a;' ?>">
      <span class="w-1.5 h-1.5 rounded-full <?= $__aiMode['active'] ? 'bg-emerald-500' : 'bg-amber-500' ?> animate-pulse"></span>
      <?= e($__aiMode['label']) ?>
    </span>

    <?php if ($user): ?>
      <nav class="hidden md:flex items-center gap-1">
        <?php
        $navItems = [];
        if ($user['role'] === 'siswa') {
            $navItems = [
                ['dashboard.php', 'Dashboard', '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
                ['tasks.php', 'Studi Kasus', '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><line x1="10" x2="8" y1="9" y2="9"/>'],
                ['profile.php', 'Profil Skill', '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
                ['mentor.php', 'AI Mentor', '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
            ];
        } else {
            $navItems = [
                ['dashboard.php', 'Dashboard', '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>'],
                ['tasks.php', 'Kelola Task', '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/>'],
                ['company/talent.php', 'Talent Pool', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
            ];
        }
        foreach ($navItems as $item):
            $isActive = $currentPage === $item[0] || ($item[0] === 'tasks.php' && $currentPage === 'task.php');
        ?>
          <a href="<?= APP_URL ?>/<?= $item[0] ?>" class="nav-link <?= $isActive ? 'active' : '' ?> flex items-center gap-2">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $item[2] ?></svg>
            <?= $item[1] ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <div class="flex items-center gap-3">
        <div class="hidden sm:flex items-center gap-3">
          <div class="avatar avatar-sm"><?= e(initials($user['name'])) ?></div>
          <div class="text-right">
            <p class="text-xs font-semibold leading-tight"><?= e($user['name']) ?></p>
            <p class="text-[10px] text-[#737373] capitalize"><?= e($user['role']) ?></p>
          </div>
        </div>
        <a href="<?= APP_URL ?>/logout.php" class="btn btn-ghost btn-sm hidden sm:inline-flex">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
          Keluar
        </a>
        <button class="hamburger md:hidden" id="hamburgerBtn" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    <?php else: ?>
      <div class="flex items-center gap-3">
        <a href="<?= APP_URL ?>/login.php" class="btn btn-ghost btn-sm">Masuk</a>
        <a href="<?= APP_URL ?>/upload_cv.php" class="btn btn-primary btn-sm">Unggah CV</a>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($user): ?>
  <div id="mobileMenu" class="mobile-menu md:hidden">
    <div class="flex items-center gap-3 p-4 mb-4 surface rounded-2xl">
      <div class="avatar avatar-lg"><?= e(initials($user['name'])) ?></div>
      <div>
        <p class="font-semibold"><?= e($user['name']) ?></p>
        <p class="text-xs text-[#737373] capitalize"><?= e($user['role']) ?></p>
      </div>
    </div>
    <nav class="flex flex-col gap-1">
      <?php foreach ($navItems as $item):
          $isActive = $currentPage === $item[0] || ($item[0] === 'tasks.php' && $currentPage === 'task.php');
      ?>
        <a href="<?= APP_URL ?>/<?= $item[0] ?>" class="nav-link <?= $isActive ? 'active' : '' ?> flex items-center gap-3 py-3">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $item[2] ?></svg>
          <?= $item[1] ?>
        </a>
      <?php endforeach; ?>
      <hr class="my-3 divider">
      <a href="<?= APP_URL ?>/logout.php" class="nav-link flex items-center gap-3 py-3 text-red-600 hover:bg-red-50">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
        Keluar
      </a>
    </nav>
  </div>
  <?php endif; ?>
</header>

<div class="h-16"></div>

<main>
<?php foreach (get_flashes() as $f): ?>
  <div class="max-w-7xl mx-auto px-6 pt-4 animate-fade-up">
    <div class="rounded-2xl px-5 py-3.5 text-sm font-medium flex items-center gap-3 <?= $f['type']==='error' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-neutral-100 text-neutral-700 border border-neutral-200' ?>">
      <?php if ($f['type']==='error'): ?>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/></svg>
      <?php else: ?>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <?php endif; ?>
      <?= e($f['message']) ?>
    </div>
  </div>
<?php endforeach; ?>
