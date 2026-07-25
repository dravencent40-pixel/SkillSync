<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/agents/AIClient.php';
require_once __DIR__ . '/../includes/agents/MentorAgent.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = current_user();
$input = json_decode(file_get_contents('php://input'), true);
$conversationId = (int) ($input['conversation_id'] ?? 0);
$message = trim($input['message'] ?? '');

if ($conversationId === 0 || $message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap']);
    exit;
}

$pdo = db();

// Pastikan percakapan milik user ini
$check = $pdo->prepare('SELECT * FROM mentor_conversations WHERE id = ? AND user_id = ?');
$check->execute([$conversationId, $user['id']]);
$conversation = $check->fetch();
if (!$conversation) {
    http_response_code(403);
    echo json_encode(['error' => 'Percakapan tidak ditemukan']);
    exit;
}

// Simpan pesan user
$pdo->prepare("INSERT INTO mentor_messages (conversation_id, sender, message) VALUES (?, 'user', ?)")
    ->execute([$conversationId, $message]);

// Ambil riwayat & konteks review terkait (jika ada)
$histStmt = $pdo->prepare('SELECT sender, message FROM mentor_messages WHERE conversation_id = ? ORDER BY created_at ASC LIMIT 20');
$histStmt->execute([$conversationId]);
$history = $histStmt->fetchAll();

$reviewContext = null;
if ($conversation['submission_id']) {
    $r = $pdo->prepare('SELECT clean_code_score, security_score, efficiency_score, overall_score, summary FROM ai_reviews WHERE submission_id = ?');
    $r->execute([$conversation['submission_id']]);
    $reviewContext = $r->fetch() ?: null;
}

$reply = (new MentorAgent())->reply($history, $message, $reviewContext);

$pdo->prepare("INSERT INTO mentor_messages (conversation_id, sender, message) VALUES (?, 'agent', ?)")
    ->execute([$conversationId, $reply]);

log_activity($user['id'], 'mentor_reply', mb_substr($message, 0, 80));

echo json_encode(['reply' => $reply]);
