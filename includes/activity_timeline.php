<?php
/**
 * SkillSync AI — Agent Activity Timeline
 * Menampilkan jejak kerja nyata tiap agent (Task Issuer, Reviewer & Auditor,
 * Mentor, Profile Generator) dari tabel activity_logs. Ini yang membuat
 * "multi-agent system" di proposal terlihat nyata bekerja, bukan cuma klaim
 * di atas kertas — siswa/mitra bisa lihat sendiri kapan agent mana beraksi.
 *
 * @param array $activity  hasil recent_activity()
 */
function render_activity_timeline(array $activity): void
{
    if (empty($activity)):
        ?>
        <div class="surface rounded-3xl p-10">
          <div class="empty-state">
            <div class="empty-state-icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <p class="empty-state-title">Belum ada aktivitas agent</p>
            <p class="empty-state-desc">Riwayat kerja Task Issuer, Reviewer, Mentor, dan Profile Generator akan muncul di sini.</p>
          </div>
        </div>
        <?php
        return;
    endif;
    ?>
    <div class="surface rounded-3xl p-6">
      <div class="space-y-5">
        <?php foreach ($activity as $i => $a):
            [$label, $iconPath] = activity_label($a['action']);
        ?>
        <div class="flex gap-4 <?= $i < count($activity) - 1 ? 'pb-5' : '' ?>" style="<?= $i < count($activity) - 1 ? 'border-bottom: 1px solid var(--border-light);' : '' ?>">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: #f5f5f5;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $iconPath ?></svg>
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-[var(--ink)]"><?= e($label) ?></p>
            <?php if (!empty($a['meta'])): ?>
              <p class="text-xs text-[var(--muted)] mt-0.5"><?= e($a['meta']) ?></p>
            <?php endif; ?>
            <p class="text-[11px] text-[var(--muted-light)] mt-1"><?= time_ago($a['created_at']) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
}
