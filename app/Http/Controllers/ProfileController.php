<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $pdo = db();

        $stmt = $pdo->prepare(
            'SELECT sp.*, s.jurusan, s.kelas, s.sekolah, s.cv_path, s.cv_original_name, s.cv_uploaded_at
             FROM skill_profiles sp
             LEFT JOIN student_profiles s ON s.user_id = sp.user_id
             WHERE sp.user_id = ?'
        );
        $stmt->execute([$user->id]);
        $profile = $stmt->fetch() ?: ['overall_score' => 0, 'clean_code_avg' => 0, 'security_avg' => 0, 'efficiency_avg' => 0, 'tasks_completed' => 0, 'badge' => 'Pemula', 'strengths' => null, 'weaknesses' => null, 'is_public' => 1, 'jurusan' => null, 'kelas' => null, 'sekolah' => null, 'cv_path' => null, 'cv_original_name' => null, 'cv_uploaded_at' => null];

        $tracksStmt = $pdo->prepare(
            'SELECT spt.*, c.name AS category_name, c.rubric_criteria
             FROM skill_profile_tracks spt
             JOIN task_categories c ON c.id = spt.category_id
             WHERE spt.user_id = ? AND spt.tasks_completed > 0
             ORDER BY spt.tasks_completed DESC, spt.overall_score DESC'
        );
        $tracksStmt->execute([$user->id]);
        $tracks = $tracksStmt->fetchAll();

        $historyStmt = $pdo->prepare(
            'SELECT s.id, s.submitted_at, t.title, r.overall_score, r.clean_code_score, r.security_score, r.efficiency_score
             FROM submissions s JOIN tasks t ON t.id = s.task_id
             LEFT JOIN ai_reviews r ON r.submission_id = s.id
             WHERE s.user_id = ? ORDER BY s.submitted_at DESC'
        );
        $historyStmt->execute([$user->id]);

        return Inertia::render('Profile', [
            'profile' => $profile,
            'tracks' => $this->withRubric($tracks),
            'history' => $historyStmt->fetchAll(),
        ]);
    }

    public function togglePublic(Request $request): RedirectResponse
    {
        db()->prepare('UPDATE skill_profiles SET is_public = 1 - is_public WHERE user_id = ?')
            ->execute([Auth::id()]);

        return redirect()->route('profile');
    }

    private function withRubric(array $tracks): array
    {
        return array_map(function ($track) {
            $rubric = task_rubric($track);
            $track['rubric'] = [
                ['score' => $track['criterion1_score'], 'label' => $rubric[0]['label']],
                ['score' => $track['criterion2_score'], 'label' => $rubric[1]['label']],
                ['score' => $track['criterion3_score'], 'label' => $rubric[2]['label']],
            ];
            return $track;
        }, $tracks);
    }
}
