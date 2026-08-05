</main>

<footer class="mt-24 border-t border-[var(--border-light)]" style="background: linear-gradient(180deg, #f7f6f2 0%, #f0efe9 100%);">
  <div class="max-w-7xl mx-auto px-6 py-12">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-10">
      <div class="md:col-span-5">
        <a href="<?= APP_URL ?>" class="flex items-center gap-3">
          <div class="logo-tile w-9 h-9">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
          </div>
          <span class="font-bold tracking-tight text-lg">SkillSync <span class="text-accent"></span></span>
        </a>
        <p class="mt-4 text-sm text-[var(--muted)] leading-relaxed max-w-md">
          Platform asesmen teknis berbasis AI yang mengotomatisasi penilaian studi kasus industri, menyediakan mentor interaktif, serta menyajikan profil kompetensi transparan.
        </p>
      </div>
      <div class="md:col-span-3 md:col-start-7">
        <h4 class="text-xs font-bold uppercase tracking-wider text-[var(--muted-light)] mb-4">Platform</h4>
        <ul class="space-y-2.5">
          <li><a href="<?= APP_URL ?>/company/talent.php" class="text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors">Talent Pool</a></li>
          <li><a href="<?= APP_URL ?>/login.php" class="text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors">Masuk</a></li>
          <li><a href="<?= APP_URL ?>/register.php" class="text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors">Daftar</a></li>
        </ul>
      </div>
      <div class="md:col-span-4">
        <h4 class="text-xs font-bold uppercase tracking-wider text-[var(--muted-light)] mb-4">Kontak</h4>
        <p class="text-sm text-[var(--muted)] mb-3">Dibuat oleh Kelompok Tekabe &middot; Kategori Pendidikan / Inovatif</p>
        <div class="flex flex-col gap-2">
          <a href="mailto:taufiqridhoo34@gmail.com" class="text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors flex items-center gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            taufiqridhoo34@gmail.com
          </a>
          <a href="mailto:riwantoraihan@gmail.com" class="text-sm text-[var(--muted)] hover:text-[var(--ink)] transition-colors flex items-center gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            riwantoraihan@gmail.com
          </a>
        </div>
      </div>
    </div>
    <hr class="my-8 divider">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
      <p class="text-xs text-[var(--muted-light)]">&copy; <?= date('Y') ?> SkillSync. All rights reserved.</p>
      <p class="text-[11px] font-mono text-[var(--muted-light)]">Powered by Groq &middot; Llama-3.3-70B</p>
    </div>
  </div>
</footer>

<div id="previewModal" class="modal-overlay" aria-hidden="true">
  <div class="modal-card">
    <aside class="modal-aside">
      <div class="flex items-center justify-between gap-2 mb-4">
        <div>
          <h4 class="text-sm font-semibold">Pratinjau CV</h4>
          <p class="text-xs text-[var(--muted)]">Lihat dokumen di sini</p>
        </div>
        <button class="modal-close" aria-label="Tutup">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/></svg>
        </button>
      </div>
      <div class="modal-meta text-sm text-[var(--muted)]">—</div>
      <div class="mt-4 p-3 rounded-xl bg-[var(--paper-soft)] border border-[var(--border)]">
        <p class="text-xs text-[var(--muted)] flex items-center gap-2">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
          Tekan Esc untuk menutup
        </p>
      </div>
    </aside>
    <div class="modal-body">
      <iframe class="modal-iframe" src="about:blank" title="Pratinjau CV"></iframe>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js?v=2"></script>
</body>
</html>
