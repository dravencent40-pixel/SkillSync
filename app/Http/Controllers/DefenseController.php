<?php

namespace App\Http\Controllers;

use App\Models\DefenseQuestion;
use App\Models\DefenseSession;
use App\Models\Submission;
use App\Services\Agents\DefenseAgent;
use App\Services\Agents\ProfileGeneratorAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DefenseController extends Controller
{
    public function show(Request $request, Submission $submission): Response|RedirectResponse
    {
        $user = Auth::user();

        $stmt = db()->prepare(
            'SELECT s.*, t.title AS task_title
             FROM submissions s JOIN tasks t ON t.id = s.task_id
             WHERE s.id = ?'
        );
        $stmt->execute([$submission->id]);
        $row = $stmt->fetch();

        if (!$row || ($user->role === 'siswa' && (int) $row['user_id'] !== (int) $user->id)) {
            session()->flash('error', 'Submission tidak ditemukan.');
            return Inertia::location(route('dashboard'));
        }

        $sessionStmt = db()->prepare('SELECT * FROM defense_sessions WHERE submission_id = ?');
        $sessionStmt->execute([$submission->id]);
        $session = $sessionStmt->fetch();

        if (!$session) {
            session()->flash('error', 'Sesi pembelaan belum tersedia untuk submission ini.');
            return Inertia::location(route('submission.show', $submission->id));
        }

        $qStmt = db()->prepare('SELECT * FROM defense_questions WHERE session_id = ? ORDER BY order_index ASC');
        $qStmt->execute([$session['id']]);

        return Inertia::render('Defense', [
            'submission' => $row,
            'session' => $session,
            'questions' => $qStmt->fetchAll(),
        ]);
    }

    public function submit(Request $request, Submission $submission): RedirectResponse
    {
        $user = Auth::user();

        $pdo = db();
        $stmt = $pdo->prepare(
            'SELECT s.*, t.title AS task_title
             FROM submissions s JOIN tasks t ON t.id = s.task_id
             WHERE s.id = ?'
        );
        $stmt->execute([$submission->id]);
        $row = $stmt->fetch();

        if (!$row || (int) $row['user_id'] !== (int) $user->id) {
            session()->flash('error', 'Submission tidak ditemukan.');
            return redirect()->route('dashboard');
        }

        $sessionStmt = $pdo->prepare('SELECT * FROM defense_sessions WHERE submission_id = ?');
        $sessionStmt->execute([$submission->id]);
        $session = $sessionStmt->fetch();

        if (!$session || $session['status'] !== 'pending') {
            return redirect()->route('defense.show', $submission->id);
        }

        $qStmt = $pdo->prepare('SELECT * FROM defense_questions WHERE session_id = ? ORDER BY order_index ASC');
        $qStmt->execute([$session['id']]);
        $questions = $qStmt->fetchAll();

        $answers = $request->input('answers', []);
        $qa = [];
        foreach ($questions as $q) {
            $ans = trim((string) ($answers[$q['id']] ?? ''));
            $qa[] = ['id' => (int) $q['id'], 'question' => $q['question'], 'answer' => $ans];
        }

        if (count(array_filter($qa, fn ($x) => $x['answer'] !== '')) === 0) {
            return back()->withErrors(['form' => 'Jawab minimal satu pertanyaan sebelum submit.']);
        }

        $pdo->prepare("UPDATE defense_sessions SET status='answered', answered_at=NOW() WHERE id = ?")->execute([$session['id']]);
        $updAns = $pdo->prepare('UPDATE defense_questions SET answer = ? WHERE id = ?');
        foreach ($qa as $item) {
            $updAns->execute([$item['answer'], $item['id']]);
        }

        $result = (new DefenseAgent())->evaluateAnswers($row['code_content'], $qa);

        $updScore = $pdo->prepare('UPDATE defense_questions SET answer_score = ?, answer_feedback = ? WHERE id = ?');
        foreach ($result['per_question'] as $i => $pq) {
            if (isset($qa[$i])) {
                $updScore->execute([(int) ($pq['score'] ?? 0), $pq['feedback'] ?? '', $qa[$i]['id']]);
            }
        }

        $pdo->prepare("UPDATE defense_sessions SET status='evaluated', comprehension_score=?, feedback=?, ai_assisted=?, evaluated_at=NOW() WHERE id = ?")
            ->execute([$result['comprehension_score'], $result['feedback'], $result['ai_assisted'] ? 1 : 0, $session['id']]);

        (new ProfileGeneratorAgent())->regenerate($user->id);
        log_activity($user->id, 'defense_completed', "\"{$row['task_title']}\" · skor pemahaman {$result['comprehension_score']}/100");

        session()->flash('success', 'Sesi pembelaan selesai dan sudah dinilai.');
        return redirect()->route('defense.show', $submission->id);
    }
}
