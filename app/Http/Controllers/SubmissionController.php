<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionController extends Controller
{
    public function show(Request $request, Submission $submission): Response|RedirectResponse
    {
        $user = Auth::user();

        $stmt = db()->prepare(
            'SELECT s.*, t.title AS task_title, t.case_brief, u.name AS student_name,
                    c.submission_type, c.rubric_criteria
             FROM submissions s
             JOIN tasks t ON t.id = s.task_id
             JOIN task_categories c ON c.id = t.category_id
             JOIN users u ON u.id = s.user_id
             WHERE s.id = ?'
        );
        $stmt->execute([$submission->id]);
        $row = $stmt->fetch();

        if (!$row || ($user->role === 'siswa' && (int) $row['user_id'] !== (int) $user->id)) {
            session()->flash('error', 'Submission tidak ditemukan.');
            return Inertia::location(route('dashboard'));
        }

        $reviewStmt = db()->prepare('SELECT * FROM ai_reviews WHERE submission_id = ?');
        $reviewStmt->execute([$submission->id]);
        $review = $reviewStmt->fetch();
        $findings = $review ? json_decode($review['findings_json'], true) : [];

        $defStmt = db()->prepare('SELECT * FROM defense_sessions WHERE submission_id = ?');
        $defStmt->execute([$submission->id]);
        $defenseSession = $defStmt->fetch();

        return Inertia::render('Submission', [
            'submission' => $row,
            'review' => $review,
            'findings' => $findings ?: [],
            'rubric' => task_rubric($row),
            'isImageFile' => (bool) ($row['file_path'] && preg_match('/\.(png|jpe?g|webp)$/i', $row['file_path'])),
            'defenseSession' => $defenseSession,
        ]);
    }
}
