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
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-[#e5e5e5] mb-6" style="background: #f5f5f5;">
          <span class="w-2 h-2 rounded-full bg-[#0a0a0a] animate-pulse"></span>
          <span class="text-xs font-semibold tracking-wider text-[#525252]">LOMBA AI AGENT INNOVATION &middot; GOODEVA</span>
        </div>

        <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold tracking-tight leading-[1.05]">
          <span>AI Technical Lead,</span><br>
          <span class="text-[#525252]">siap kerja 24 jam.</span>
        </h1>

        <p class="mt-6 text-base md:text-lg text-[#525252] leading-relaxed max-w-[52ch]">
          SkillSync AI mengotomatisasi penilaian studi kasus industri lewat automated code audit, interactive AI mentor, serta competency profile transparan yang terhubung ke hiring partner.
        </p>

        <div class="mt-9 flex flex-wrap gap-3">
          <a href="<?= APP_URL ?>/upload_cv.php" class="btn btn-primary btn-lg">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
            Unggah CV Sekarang
          </a>
          <a href="<?= APP_URL ?>/company/talent.php" class="btn btn-ghost btn-lg">
            Lihat Talent Pool
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" x2="19" y1="12" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          </a>
        </div>

        <div class="mt-10 flex items-center gap-6 text-sm text-[#525252]">
          <div class="flex items-center gap-2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
            <span><strong class="text-[#0a0a0a]">4</strong> AI Agent bekerja sama</span>
          </div>
          <div class="w-1 h-1 rounded-full bg-[#d4d4d4]"></div>
          <div class="flex items-center gap-2">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
            Skor kompetensi <strong class="text-[#0a0a0a]">real-time</strong>
          </div>
        </div>
      </div>

      <div class="lg:col-span-5 animate-fade-up" style="animation-delay: 0.1s;">
        <div class="surface spot-card p-6 rounded-3xl">
          <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
              <div class="w-2 h-2 rounded-full bg-[#0a0a0a] animate-pulse"></div>
              <p class="text-xs font-semibold uppercase tracking-wider text-[#a3a3a3]">Live Audit Preview</p>
            </div>
            <span class="badge badge-accent">AI Powered</span>
          </div>
          <pre class="rounded-2xl text-xs p-5 overflow-x-auto leading-relaxed" style="background: #0a0a0a; color: #e5e5e5;"><span style="color: #737373;">// login.php</span>
$query = <span style="color: #a3a3a3;">"SELECT * FROM users WHERE u='$u'"</span>;
<span style="color: #737373;">// ⚠ Agent Reviewer menandai baris ini</span></pre>
          <div class="mt-5 grid grid-cols-3 gap-3">
            <div class="text-center p-3 rounded-xl border border-[#e5e5e5]" style="background: #f5f5f5;">
              <p class="text-lg font-bold text-[#0a0a0a]">42</p>
              <p class="text-[10px] font-medium text-[#737373] mt-0.5">Keamanan</p>
            </div>
            <div class="text-center p-3 rounded-xl border border-[#e5e5e5]" style="background: #f5f5f5;">
              <p class="text-lg font-bold text-[#525252]">78</p>
              <p class="text-[10px] font-medium text-[#737373] mt-0.5">Clean Code</p>
            </div>
            <div class="text-center p-3 rounded-xl border border-[#e5e5e5]" style="background: #f5f5f5;">
              <p class="text-lg font-bold text-[#0a0a0a]">85</p>
              <p class="text-[10px] font-medium text-[#737373] mt-0.5">Efisiensi</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section-accent max-w-7xl mx-auto px-6 py-20">
  <div class="text-center max-w-lg mx-auto mb-14">
    <h2 class="text-3xl md:text-4xl font-bold tracking-tight">
      Empat agent, satu<br><span class="text-[#525252]">alur kerja Senior Tech Lead</span>
    </h2>
    <p class="mt-4 text-[#525252] text-sm md:text-base">Setiap agent memiliki peran spesifik dalam pipeline asesmen teknis siswa.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-5 stagger">
    <?php
    $agents = [
        ['01', 'Agent Task Issuer', 'Mengeluarkan studi kasus & kodingan riil dari industri, dipersonalisasi berdasarkan kategori terlemah tiap siswa.', 'M16 11V7a4 4 0 0 0-8 0v4M5 9h14a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V10a1 1 0 0 1 1-1z'],
        ['02', 'Agent Reviewer & Auditor', 'Menilai source code secara otomatis: clean code, keamanan (SQLi/XSS), dan efisiensi (N+1 query, kompleksitas).', 'M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
        ['03', 'Agent Mentor', 'Chatbot interaktif yang membimbing perbaikan bug dengan hint bertahap, bukan sekadar jawaban jadi.', 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 0 1-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        ['04', 'Agent Profile Generator', 'Mengagregasi seluruh hasil audit menjadi skor kompetensi transparan, siap direkomendasikan ke mitra.', 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0zM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7z'],
    ];
    foreach ($agents as $i => $a): ?>
    <div class="surface surface-hover spot-card p-7 rounded-2xl group">
      <div class="flex items-start gap-5">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 transition-all duration-300 group-hover:scale-110" style="background: #f5f5f5; color: #0a0a0a;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $a[3] ?>"/></svg>
        </div>
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-1">
            <span class="text-[10px] font-bold tracking-wider text-[#a3a3a3]"><?= $a[0] ?></span>
            <div class="w-4 h-px bg-[#e5e5e5]"></div>
          </div>
          <h3 class="font-bold text-lg leading-snug"><?= e($a[1]) ?></h3>
          <p class="mt-2 text-sm text-[#525252] leading-relaxed"><?= e($a[2]) ?></p>
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
      <h2 class="text-3xl md:text-4xl font-bold tracking-tight text-white">Mengapa SkillSync AI?</h2>
      <p class="mt-3 text-sm text-neutral-400 max-w-md mx-auto">Solusi asesmen teknis yang transparan, efisien, dan terhubung langsung dengan industri.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 stagger">
      <div class="p-7 rounded-2xl border border-white/10" style="background: rgba(255,255,255,0.08);">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5" style="background: rgba(255,255,255,0.1);">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <p class="text-3xl font-extrabold text-white">100%</p>
        <p class="mt-2 text-sm text-neutral-400 leading-relaxed">Transparansi skor kompetensi teknis siswa, tanpa bias CV konvensional.</p>
      </div>
      <div class="p-7 rounded-2xl border border-white/10" style="background: rgba(255,255,255,0.08);">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5" style="background: rgba(255,255,255,0.1);">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <p class="text-3xl font-extrabold text-white">24/7</p>
        <p class="mt-2 text-sm text-neutral-400 leading-relaxed">Mentor AI siap membimbing kapan saja, tidak terbatas jam kerja pembimbing.</p>
      </div>
      <div class="p-7 rounded-2xl border border-white/10" style="background: rgba(255,255,255,0.08);">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center mb-5" style="background: rgba(255,255,255,0.1);">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
        </div>
        <p class="text-3xl font-extrabold text-white">1 Klik</p>
        <p class="mt-2 text-sm text-neutral-400 leading-relaxed">Mitra industri langsung menyaring talenta siap magang dari Talent Pool.</p>
      </div>
    </div>
  </div>
</section>

<section class="max-w-7xl mx-auto px-6 py-20">
  <div class="surface p-10 md:p-14 rounded-3xl text-center relative overflow-hidden">
    <div class="blob" style="width: 300px; height: 300px; top: -100px; right: -50px;"></div>
    <h2 class="text-2xl md:text-3xl font-bold tracking-tight relative z-10">Siap mengasah kemampuan teknismu?</h2>
    <p class="mt-3 text-[#525252] text-sm md:text-base max-w-md mx-auto relative z-10">Unggah CV dan mulai kerjakan studi kasus industri yang dinilai langsung oleh AI.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3 relative z-10">
      <a href="<?= APP_URL ?>/upload_cv.php" class="btn btn-primary btn-lg">
        Mulai Sekarang
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" x2="19" y1="12" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
