<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_role('siswa');
$user = current_user();
$pdo = db();

$submissionId = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : null;
$taskTitle = null;
if ($submissionId) {
    $chk = $pdo->prepare('SELECT s.id, t.title FROM submissions s JOIN tasks t ON t.id = s.task_id WHERE s.id = ? AND s.user_id = ?');
    $chk->execute([$submissionId, $user['id']]);
    $row = $chk->fetch();
    if (!$row) { $submissionId = null; } else { $taskTitle = $row['title']; }
}

$convStmt = $pdo->prepare('SELECT * FROM mentor_conversations WHERE user_id = ? AND submission_id ' . ($submissionId ? '= ?' : 'IS NULL') . ' ORDER BY created_at DESC LIMIT 1');
$submissionId ? $convStmt->execute([$user['id'], $submissionId]) : $convStmt->execute([$user['id']]);
$conversation = $convStmt->fetch();

if (!$conversation) {
    $title = $taskTitle ? 'Diskusi: ' . $taskTitle : 'Sesi Mentoring';
    $pdo->prepare('INSERT INTO mentor_conversations (submission_id, user_id, title) VALUES (?,?,?)')
        ->execute([$submissionId, $user['id'], $title]);
    $convId = (int) $pdo->lastInsertId();
    $conversation = ['id' => $convId, 'title' => $title];

    $welcome = $taskTitle
        ? "Halo {$user['name']}! Aku sudah lihat hasil audit untuk \"{$taskTitle}\". Ada bagian yang mau kamu diskusikan lebih lanjut?"
        : "Halo {$user['name']}! Aku Agent Mentor SkillSync AI. Ceritakan apa yang sedang kamu kerjakan.";
    $pdo->prepare("INSERT INTO mentor_messages (conversation_id, sender, message) VALUES (?, 'agent', ?)")->execute([$convId, $welcome]);
}

$msgStmt = $pdo->prepare('SELECT * FROM mentor_messages WHERE conversation_id = ? ORDER BY created_at ASC');
$msgStmt->execute([$conversation['id']]);
$messages = $msgStmt->fetchAll();

$pageTitle = 'AI Mentor';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-3xl mx-auto px-6 py-10 flex flex-col" style="height: calc(100dvh - 6.5rem);">
  <!-- Header -->
  <div class="flex items-center gap-4 mb-4 animate-fade-up shrink-0">
    <div class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background: var(--gradient-dark); box-shadow: 0 2px 8px rgba(15,23,42,0.2);">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    </div>
    <div>
      <h1 class="text-lg font-bold">Agent Mentor</h1>
      <p class="text-xs text-[var(--muted)] flex items-center gap-1.5">
        <span class="w-1.5 h-1.5 rounded-full bg-neutral-400 animate-pulse"></span>
        <?= $taskTitle ? 'Konteks: ' . e($taskTitle) : 'Sesi bebas' ?>
      </p>
    </div>
  </div>

  <!-- Chat Window -->
  <div id="chatWindow" class="flex-1 surface rounded-3xl p-5 overflow-y-auto flex flex-col gap-4 min-h-0">
    <?php foreach ($messages as $m): ?>
      <?php if ($m['sender'] === 'agent'): ?>
        <div class="flex gap-3 max-w-[85%] animate-fade-up">
          <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs font-bold" style="background: var(--gradient-dark); color: white;">M</div>
          <div class="bubble-agent px-4 py-3 text-sm leading-relaxed"><?= nl2br(e($m['message'])) ?></div>
        </div>
      <?php else: ?>
        <div class="flex gap-3 max-w-[85%] ml-auto flex-row-reverse animate-fade-up">
          <div class="avatar avatar-sm shrink-0"><?= e(initials($user['name'])) ?></div>
          <div class="bubble-user px-4 py-3 text-sm leading-relaxed"><?= nl2br(e($m['message'])) ?></div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <!-- Input Area -->
  <form id="chatForm" class="mt-4 flex items-end gap-3 shrink-0 animate-fade-up" style="animation-delay: 0.1s;">
    <input type="hidden" id="conversationId" value="<?= (int)$conversation['id'] ?>">
    <div class="flex-1 relative">
      <textarea id="chatInput" rows="1" required placeholder="Tanyakan tentang bug, konsep, atau feedback kode kamu…"
        class="w-full resize-none pr-12 py-3 px-4 rounded-2xl border-2 border-[var(--border)] focus:border-neutral-400 text-sm"></textarea>
    </div>
    <button type="submit" id="sendBtn" class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 text-white transition-all duration-200 hover:scale-105 active:scale-95" style="background: var(--gradient-dark); box-shadow: 0 2px 8px rgba(15,23,42,0.2);">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" x2="11" y1="2" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
    </button>
  </form>
</section>

<script>
const chatWindow = document.getElementById('chatWindow');
const chatForm = document.getElementById('chatForm');
const chatInput = document.getElementById('chatInput');
const sendBtn = document.getElementById('sendBtn');
const conversationId = document.getElementById('conversationId').value;

chatWindow.scrollTop = chatWindow.scrollHeight;

function bubble(sender, text) {
  const wrap = document.createElement('div');
  wrap.className = 'flex gap-3 max-w-[85%] animate-fade-up' + (sender === 'user' ? ' ml-auto flex-row-reverse' : '');
  const initials = sender === 'agent' ? 'M' : '<?= e(initials($user["name"])) ?>';
  const avatarStyle = sender === 'agent'
    ? 'background: var(--gradient-dark); color: white; border-radius: 0.75rem; width: 2rem; height: 2rem; display: grid; place-items: center; font-size: 0.6875rem; font-weight: 700; flex-shrink: 0;'
    : 'background: var(--gradient-dark); color: white; border-radius: 0.75rem; width: 2rem; height: 2rem; display: grid; place-items: center; font-size: 0.6875rem; font-weight: 700; flex-shrink: 0;';
  const bubbleClass = sender === 'agent' ? 'bubble-agent' : 'bubble-user';
  wrap.innerHTML = `<span style="${avatarStyle}">${initials}</span>
                     <div class="${bubbleClass} px-4 py-3 text-sm leading-relaxed"></div>`;
  wrap.querySelector('div').textContent = text;
  chatWindow.appendChild(wrap);
  chatWindow.scrollTop = chatWindow.scrollHeight;
}

function typingIndicator(show) {
  let el = document.getElementById('typingIndicator');
  if (show) {
    if (el) return;
    el = document.createElement('div');
    el.id = 'typingIndicator';
    el.className = 'flex gap-3 max-w-[85%] animate-fade-up';
    el.innerHTML = `<span style="background: var(--gradient-dark); color: white; border-radius: 0.75rem; width: 2rem; height: 2rem; display: grid; place-items: center; font-size: 0.6875rem; font-weight: 700; flex-shrink: 0;">M</span>
      <div class="bubble-agent px-4 py-3 flex gap-1.5 items-center">
        <span class="typing-dot w-2 h-2 rounded-full" style="background: var(--muted-light)"></span>
        <span class="typing-dot w-2 h-2 rounded-full" style="background: var(--muted-light)"></span>
        <span class="typing-dot w-2 h-2 rounded-full" style="background: var(--muted-light)"></span>
      </div>`;
    chatWindow.appendChild(el);
    chatWindow.scrollTop = chatWindow.scrollHeight;
  } else if (el) {
    el.remove();
  }
}

chatForm.addEventListener('submit', async function (e) {
  e.preventDefault();
  const text = chatInput.value.trim();
  if (!text) return;
  bubble('user', text);
  chatInput.value = '';
  sendBtn.disabled = true;
  typingIndicator(true);

  try {
    const res = await fetch('<?= APP_URL ?>/api/chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ conversation_id: conversationId, message: text })
    });
    const data = await res.json();
    typingIndicator(false);
    bubble('agent', data.reply || 'Maaf, terjadi kendala. Coba lagi ya.');
  } catch (err) {
    typingIndicator(false);
    bubble('agent', 'Koneksi terganggu, coba kirim ulang pesanmu.');
  }
  sendBtn.disabled = false;
});

chatInput.addEventListener('keydown', function (e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    chatForm.requestSubmit();
  }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
