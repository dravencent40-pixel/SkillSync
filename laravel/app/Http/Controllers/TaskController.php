<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\Agents\DefenseAgent;
use App\Services\Agents\ProfileGeneratorAgent;
use App\Services\Agents\ReviewerAuditorAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $pdo = db();

        $categories = $pdo->query('SELECT * FROM task_categories ORDER BY name')->fetchAll();

        if ($user->role === 'siswa') {
            $stmt = $pdo->prepare(
                "SELECT t.*, c.name AS category_name,
                        (SELECT COUNT(*) FROM submissions s WHERE s.task_id = t.id AND s.user_id = ?) AS done
                 FROM tasks t JOIN task_categories c ON c.id = t.category_id
                 WHERE t.is_active = 1 ORDER BY t.created_at DESC"
            );
            $stmt->execute([$user->id]);
        } else {
            $stmt = $pdo->prepare(
                "SELECT t.*, c.name AS category_name,
                        (SELECT COUNT(*) FROM submissions s WHERE s.task_id = t.id) AS submission_count
                 FROM tasks t JOIN task_categories c ON c.id = t.category_id
                 ORDER BY t.created_at DESC"
            );
            $stmt->execute();
        }

        return Inertia::render('Tasks', [
            'tasks' => $stmt->fetchAll(),
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user->role !== 'mitra') {
            return redirect()->route('dashboard');
        }

        $data = $request->validate([
            'title'            => ['required'],
            'category_id'      => ['required', 'integer'],
            'case_brief'       => ['required'],
            'industry_context' => ['nullable', 'string'],
            'starter_code'     => ['nullable', 'string'],
            'difficulty'       => ['required', 'in:pemula,menengah,mahir'],
        ], [
            'title.required'       => 'Judul wajib diisi.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'case_brief.required'  => 'Deskripsi studi kasus wajib diisi.',
        ]);

        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $data['title']), '-')) . '-' . substr(uniqid(), -4);

        $pdo = db();
        $pdo->prepare('INSERT INTO tasks (category_id, created_by, title, slug, industry_context, case_brief, starter_code, difficulty)
                        VALUES (?,?,?,?,?,?,?,?)')
            ->execute([
                $data['category_id'], $user->id, $data['title'], $slug, $data['industry_context'] ?: null,
                $data['case_brief'], $data['starter_code'] ?: null, $data['difficulty'],
            ]);

        session()->flash('success', 'Studi kasus baru berhasil diterbitkan oleh Agent Task Issuer.');
        return redirect()->route('tasks');
    }

    public function show(Request $request, Task $task): Response
    {
        $user = Auth::user();

        $stmt = db()->prepare(
            'SELECT t.*, c.name AS category_name, c.submission_type, c.rubric_criteria
             FROM tasks t JOIN task_categories c ON c.id = t.category_id WHERE t.id = ?'
        );
        $stmt->execute([$task->id]);
        $task = $stmt->fetch();

        if (!$task) {
            session()->flash('error', 'Studi kasus tidak ditemukan.');
            return Inertia::location(route('tasks'));
        }

        $submissionType = $task['submission_type'] ?: 'code';
        $typeConfig = submission_type_config($submissionType);

        $mySubmission = null;
        if ($user->role === 'siswa') {
            $s = db()->prepare('SELECT * FROM submissions WHERE task_id = ? AND user_id = ? ORDER BY submitted_at DESC LIMIT 1');
            $s->execute([$task['id'], $user->id]);
            $mySubmission = $s->fetch();
        }

        return Inertia::render('Task', [
            'task' => $task,
            'typeConfig' => $typeConfig,
            'mySubmission' => $mySubmission,
        ]);
    }

    public function submit(Request $request, Task $task): RedirectResponse
    {
        $user = Auth::user();

        $taskRow = db()->prepare('SELECT t.*, c.submission_type, c.rubric_criteria FROM tasks t JOIN task_categories c ON c.id = t.category_id WHERE t.id = ?');
        $taskRow->execute([$task->id]);
        $task = $taskRow->fetch();
        if (!$task) {
            session()->flash('error', 'Studi kasus tidak ditemukan.');
            return redirect()->route('tasks');
        }

        $submissionType = $task['submission_type'] ?: 'code';
        $rubric = task_rubric($task);
        $content = trim((string) $request->input('code_content', ''));
        $notes = trim((string) $request->input('notes', ''));
        $externalLink = trim((string) $request->input('external_link', ''));

        $errors = [];
        if ($content === '') {
            $errors[] = $submissionType === 'code' ? 'Kode tidak boleh kosong.' : 'Penjelasan tidak boleh kosong.';
        }

        // Upload file opsional (screenshot desain, topologi jaringan, dll)
        $filePath = null;
        $fileNameForAi = null;
        if ($request->hasFile('submission_file') && empty($errors)) {
            $file = $request->file('submission_file');
            $allowed = ['image/png', 'image/jpeg', 'image/webp', 'application/pdf'];
            if (!in_array($file->getMimeType(), $allowed, true)) {
                $errors[] = 'Format file harus PNG/JPG/WEBP/PDF.';
            } elseif ($file->getSize() > 8 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 8MB.';
            } else {
                $uploadsDir = public_path('uploads/submissions');
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                $ext = $file->getClientOriginalExtension() ?: 'bin';
                $basename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                if ($file->move($uploadsDir, $basename)) {
                    $filePath = 'uploads/submissions/' . $basename;
                    $fileNameForAi = $file->getClientOriginalName();
                } else {
                    $errors[] = 'Gagal menyimpan file. Periksa izin direktori.';
                }
            }
        }

        if (!empty($errors)) {
            return back()->withErrors(['form' => implode("\n", $errors)]);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO submissions (task_id, user_id, language, code_content, file_path, external_link, notes, status) VALUES (?,?,?,?,?,?,?,\'submitted\')');
            $ins->execute([$task['id'], $user->id, $submissionType, $content, $filePath, $externalLink ?: null, $notes ?: null]);
            $submissionId = (int) $pdo->lastInsertId();

            $review = (new ReviewerAuditorAgent())->review($content, $task['case_brief'], $rubric, $submissionType, $externalLink ?: null, $fileNameForAi);

            $pdo->prepare(
                'INSERT INTO ai_reviews (submission_id, clean_code_score, security_score, efficiency_score, overall_score, summary, findings_json)
                 VALUES (?,?,?,?,?,?,?)'
            )->execute([
                $submissionId, $review['clean_code_score'], $review['security_score'], $review['efficiency_score'],
                $review['overall_score'], $review['summary'], json_encode($review['findings'], JSON_UNESCAPED_UNICODE),
            ]);
            $pdo->prepare("UPDATE submissions SET status='reviewed' WHERE id = ?")->execute([$submissionId]);

            $mode = $review['ai_assisted'] ? 'Groq AI' : 'heuristik lokal';
            log_activity($user->id, 'submission_reviewed', "\"{$task['title']}\" · skor {$review['overall_score']}/100 · mode: {$mode}");

            // Agent Defense: buat sesi pembelaan otomatis (anti-cheat)
            $defenseAgent = new DefenseAgent();
            $dq = $defenseAgent->generateQuestions($content, $task['case_brief'], $review['findings']);
            $sessionIns = $pdo->prepare('INSERT INTO defense_sessions (submission_id, status, ai_assisted) VALUES (?, \'pending\', ?)');
            $sessionIns->execute([$submissionId, $dq['ai_assisted'] ? 1 : 0]);
            $sessionId = (int) $pdo->lastInsertId();
            $qIns = $pdo->prepare('INSERT INTO defense_questions (session_id, order_index, question) VALUES (?,?,?)');
            foreach ($dq['questions'] as $i => $q) {
                $qIns->execute([$sessionId, $i, $q]);
            }

            (new ProfileGeneratorAgent())->regenerate($user->id);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            report($e);
            session()->flash('error', 'Gagal memproses submission. Coba lagi.');
            return redirect()->route('task.show', $task['id']);
        }

        return redirect()->route('submission.show', $submissionId);
    }
}
