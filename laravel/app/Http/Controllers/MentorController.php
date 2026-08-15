<?php

namespace App\Http\Controllers;

use App\Services\Agents\MentorAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MentorController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $pdo = db();

        $submissionId = $request->integer('submission_id') ?: null;
        $taskTitle = null;

        if ($submissionId) {
            $chk = $pdo->prepare('SELECT s.id, t.title FROM submissions s JOIN tasks t ON t.id = s.task_id WHERE s.id = ? AND s.user_id = ?');
            $chk->execute([$submissionId, $user->id]);
            $row = $chk->fetch();
            if (!$row) {
                $submissionId = null;
            } else {
                $taskTitle = $row['title'];
            }
        }

        $convStmt = $pdo->prepare('SELECT * FROM mentor_conversations WHERE user_id = ? AND submission_id ' . ($submissionId ? '= ?' : 'IS NULL') . ' ORDER BY created_at DESC LIMIT 1');
        $submissionId ? $convStmt->execute([$user->id, $submissionId]) : $convStmt->execute([$user->id]);
        $conversation = $convStmt->fetch();

        if (!$conversation) {
            $title = $taskTitle ? 'Diskusi: ' . $taskTitle : 'Sesi Mentoring';
            $pdo->prepare('INSERT INTO mentor_conversations (submission_id, user_id, title) VALUES (?,?,?)')
                ->execute([$submissionId, $user->id, $title]);
            $convId = (int) $pdo->lastInsertId();
            $conversation = ['id' => $convId, 'title' => $title];

            $welcome = $taskTitle
                ? "Halo {$user->name}! Aku sudah lihat hasil audit untuk \"{$taskTitle}\". Ada bagian yang mau kamu diskusikan lebih lanjut?"
                : "Halo {$user->name}! Aku Agent Mentor SkillSync. Ceritakan apa yang sedang kamu kerjakan.";
            $pdo->prepare("INSERT INTO mentor_messages (conversation_id, sender, message) VALUES (?, 'agent', ?)")->execute([$convId, $welcome]);
        }

        $msgStmt = $pdo->prepare('SELECT * FROM mentor_messages WHERE conversation_id = ? ORDER BY created_at ASC');
        $msgStmt->execute([$conversation['id']]);

        return Inertia::render('Mentor', [
            'conversationId' => (int) $conversation['id'],
            'taskTitle' => $taskTitle,
            'messages' => $msgStmt->fetchAll(),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $user = Auth::user();
        $input = $request->json()->all();
        $conversationId = (int) ($input['conversation_id'] ?? 0);
        $message = trim((string) ($input['message'] ?? ''));

        if ($conversationId === 0 || $message === '') {
            return response()->json(['error' => 'Data tidak lengkap'], 400);
        }

        $pdo = db();

        $check = $pdo->prepare('SELECT * FROM mentor_conversations WHERE id = ? AND user_id = ?');
        $check->execute([$conversationId, $user->id]);
        $conversation = $check->fetch();
        if (!$conversation) {
            return response()->json(['error' => 'Percakapan tidak ditemukan'], 403);
        }

        $pdo->prepare("INSERT INTO mentor_messages (conversation_id, sender, message) VALUES (?, 'user', ?)")
            ->execute([$conversationId, $message]);

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

        log_activity($user->id, 'mentor_reply', mb_substr($message, 0, 80));

        return response()->json(['reply' => $reply]);
    }
}
