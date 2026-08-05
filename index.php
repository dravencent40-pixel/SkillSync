<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');
$pageTitle = 'Beranda';
require __DIR__ . '/includes/header.php';
?>

<section class="relative mesh-bg overflow-hidden">
  <div class="blob animate-float" style="width: 400px; height: 400px; top: -100px; right: -100px;"></div>
  <div class="blob animate-float" style="width: 300px; height: 300px; bottom: -80px; left: -80px; animation-delay: 1.5s;"></div>

  <div class="max-w-7xl mx-auto px-6 pt-16 pb-20 md:pt-24 md:pb-28 relative z-10">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
      <div class="lg:col-span-7 animate-fade-up">

        <span class="eyebrow mb-5">Asesmen Teknis Berbasis AI</span>

        <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold tracking-tight leading-[1.04]">
          <span>AI Technical Lead,</span><br>
          <span class="text-accent">siap kerja <span class="underline decoration-accent decoration-[3px] underline-offset-8">24 jam</span>.</span>
        </h1>

        <p class="mt-6 text-base md:text-lg text-[var(--muted)] leading-relaxed max-w-[52ch]">
          SkillSync mengotomatisasi penilaian studi kasus industri lewat automated code audit, interactive AI mentor, serta competency profile transparan yang terhubung ke hiring partner.
        </p>

        <div class="mt-9 flex flex-wrap gap-3">
          <a href="<?= APP_URL ?>/register.php" class="btn btn-primary btn-lg">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
            Daftar Sekarang
          </a>
          <a href="<?= APP_URL ?>/company/talent.php" class="btn btn-ghost btn-lg">
            Lihat Talent Pool
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" x2="19" y1="12" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </a>
        </div>

        <div class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-[var(--muted)]">
          <div class="flex items-center gap-2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
            <span><strong class="num text-[var(--ink)]">4</strong> AI Agent bekerja sama</span>
          </div>
          <div class="w-1 h-1 rounded-full bg-[var(--border-strong)]"></div>
          <div class="flex items-center gap-2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Skor kompetensi <strong class="num text-[var(--ink)]">real-time</strong>
          </div>
        </div>
      </div>

      <div class="lg:col-span-5 animate-fade-up" style="animation-delay: 0.1s;">
        <div class="spot-card p-6">
          <div class="flex items-center justify-between mb-5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--muted-light)]">CV Biasa vs SkillSync</p>
            <span class="badge badge-accent">Transparan</span>
          </div>

          <div class="grid grid-cols-2 gap-4 relative">

            <div class="rounded-2xl p-4 bg-[var(--paper-soft)] border border-[var(--border)]">
              <p class="text-[10px] font-bold uppercase tracking-wider mb-3 text-[var(--muted-light)]">CV Konvensional</p>
              <div class="space-y-2.5">
                <div class="h-2.5 rounded-full bg-[var(--border)] w-[90%]"></div>
                <div class="h-2.5 rounded-full bg-[var(--border)] w-[70%]"></div>
                <div class="h-2.5 rounded-full bg-[var(--border)] w-[80%]"></div>
                <div class="h-2.5 rounded-full bg-[var(--border)] w-[55%]"></div>
              </div>
              <p class="text-xs italic mt-4 text-[var(--muted-light)]">"Menguasai berbagai bahasa programming, kompetensi baik."</p>
            </div>

            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold z-10 border-4 border-[var(--paper)]" style="background: var(--gradient-accent); box-shadow: var(--shadow-accent);">→</div>

            <div class="rounded-2xl p-4 bg-[var(--accent-50)] border border-[var(--accent-100)]">
              <div class="flex items-center justify-between mb-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--accent-dark)]">Profil SkillSync</p>
                <span class="num text-lg font-bold text-[var(--accent-dark)]">86</span>
              </div>
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="text-xs text-[var(--muted)]">Programming</span>
                  <span class="num text-sm font-bold text-[var(--accent-dark)]">88</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-xs text-[var(--muted)]">UI/UX Design</span>
                  <span class="num text-sm font-bold text-[var(--accent-dark)]">82</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-xs text-[var(--muted)]">Jaringan &amp; Infrastruktur</span>
                  <span class="num text-sm font-bold text-[var(--warning)]">79</span>
                </div>
              </div>
              <span class="inline-block mt-4 text-[10px] font-bold px-2.5 py-1 rounded-full text-white" style="background: var(--accent);">Job Ready</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="bg-slate-50 max-w-7xl mx-auto px-6 py-20">
  <div class="max-w-lg mx-auto mb-14 text-center">
    <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-slate-900">
      Empat agent, <br><span class="text-slate-600">satu alur kerja Senior Tech Lead</span>
    </h2>
    <p class="mt-4 text-slate-700 text-sm md:text-base font-medium">Setiap agent memiliki peran spesifik dalam pipeline asesmen teknis siswa.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-5 stagger">
    <?php
    $agents = [
        ['01', 'Agent Reviewer & Auditor', 'Menilai studi kasus yang dikerjakan secara otomatis.', 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
        ['02', 'Agent Mentor', 'Chatbot interaktif yang membimbing perbaikan bug dengan hint bertahap, bukan sekadar jawaban jadi.', 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 0 1-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        ['03', 'Agent Defense', 'Menguji pemahaman siswa lewat pertanyaan yang merujuk langsung ke hasil kerjanya sendiri, sebagai mitigasi kecurangan.', 'M9.09 9a3 3 0 1 1 5.83 1c0 2-3 3-3 3m.01 4h.01M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z'],
        ['04', 'Agent Profile Generator', 'Merangkum seluruh hasil studi kasus yang sudah dikerjakan menjadi profile yang bisa dilihat oleh mitra industri.', 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z'],
    ];
    foreach ($agents as $i => $a): ?>
    <div class="surface surface-hover spot-card p-7 rounded-2xl group">
      <div class="flex items-start gap-5">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 bg-[var(--accent-50)] text-[var(--accent)] border border-[var(--accent-100)] transition-all duration-300 group-hover:scale-110 group-hover:bg-[var(--accent)] group-hover:text-white">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $a[3] ?>"/></svg>
        </div>
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-1">
            <span class="num text-[10px] font-bold tracking-wider text-[var(--muted-light)]"><?= $a[0] ?></span>
            <div class="w-4 h-px bg-[var(--border)]"></div>
          </div>
          <h3 class="font-bold text-lg leading-snug"><?= e($a[1]) ?></h3>
          <p class="mt-2 text-sm text-[var(--muted)] leading-relaxed"><?= e($a[2]) ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="relative overflow-hidden" style="background: var(--gradient-dark);">
  <div class="absolute inset-0 opacity-10">
    <div class="blob" style="width: 500px; height: 500px; top: -200px; right: -100px; background: rgba(255,255,255,0.08);"></div>
    <div class="blob" style="width: 400px; height: 400px; bottom: -200px; left: -100px; background: rgba(255,255,255,0.05);"></div>
  </div>
  <div class="max-w-7xl mx-auto px-6 py-20 relative z-10">
    <div class="text-center mb-14">
      <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-white">Mengapa SkillSync?</h2>
      <p class="mt-3 text-sm text-neutral-400 max-w-md mx-auto">Solusi asesmen teknis yang transparan, efisien, dan terhubung langsung dengan industri.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 stagger">
      <div class="p-7 rounded-2xl border border-white/10" style="background: rgba(255,255,255,0.08);">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5" style="background: rgba(255,255,255,0.1);">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <p class="num text-3xl font-extrabold text-white">100%</p>
        <p class="mt-2 text-sm text-neutral-400 leading-relaxed">Transparansi skor kompetensi teknis siswa, tanpa bias CV konvensional.</p>
      </div>
      <div class="p-7 rounded-2xl border border-white/10" style="background: rgba(255,255,255,0.08);">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5" style="background: rgba(255,255,255,0.1);">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <p class="num text-3xl font-extrabold text-white">24/7</p>
        <p class="mt-2 text-sm text-neutral-400 leading-relaxed">Mentor AI siap membimbing kapan saja, tidak terbatas jam kerja pembimbing.</p>
      </div>
      <div class="p-7 rounded-2xl border border-white/10" style="background: rgba(255,255,255,0.08);">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5" style="background: rgba(255,255,255,0.1);">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
        </div>
        <p class="num text-3xl font-extrabold text-white">1 Klik</p>
        <p class="mt-2 text-sm text-neutral-400 leading-relaxed">Mitra industri langsung menyaring talenta siap magang dari Talent Pool.</p>
      </div>
    </div>
  </div>
</section>

<section class="max-w-7xl mx-auto px-6 py-20">
  <div class="surface p-10 md:p-14 rounded-3xl text-center relative overflow-hidden">
    <div class="blob" style="width: 300px; height: 300px; top: -100px; right: -50px;"></div>
    <h2 class="text-2xl md:text-3xl font-bold tracking-tight relative z-10">Siap mengasah kemampuan teknismu?</h2>
    <p class="mt-3 text-[var(--muted)] text-sm md:text-base max-w-md mx-auto relative z-10">Daftar sekarang, lalu unggah CV dan kerjakan studi kasus industri yang dinilai langsung oleh AI.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3 relative z-10">
      <a href="<?= APP_URL ?>/register.php" class="btn btn-primary btn-lg">
        Mulai Sekarang
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" x2="19" y1="12" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
