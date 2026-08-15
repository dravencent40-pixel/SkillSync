<?php

namespace App\Http\Controllers;

use App\Services\Agents\TaskIssuerAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $pdo = db();
        $activity = recent_activity($user->id, 6);

        if ($user->role === 'siswa') {
            $stmt = $pdo->prepare('SELECT * FROM skill_profiles WHERE user_id = ?');
            $stmt->execute([$user->id]);
            $profile = $stmt->fetch() ?: ['overall_score' => 0, 'clean_code_avg' => 0, 'security_avg' => 0, 'efficiency_avg' => 0, 'tasks_completed' => 0, 'badge' => 'Pemula'];

            $tracksStmt = $pdo->prepare(
                'SELECT spt.*, c.name AS category_name, c.rubric_criteria
                 FROM skill_profile_tracks spt
                 JOIN task_categories c ON c.id = spt.category_id
                 WHERE spt.user_id = ? AND spt.tasks_completed > 0
                 ORDER BY spt.tasks_completed DESC, spt.overall_score DESC'
            );
            $tracksStmt->execute([$user->id]);
            $tracks = $tracksStmt->fetchAll();

            $recentStmt = $pdo->prepare(
                'SELECT s.id, s.submitted_at, t.title, r.overall_score
                 FROM submissions s JOIN tasks t ON t.id = s.task_id
                 LEFT JOIN ai_reviews r ON r.submission_id = s.id
                 WHERE s.user_id = ? ORDER BY s.submitted_at DESC LIMIT 5'
            );
            $recentStmt->execute([$user->id]);
            $recent = $recentStmt->fetchAll();

            $recommendation = (new TaskIssuerAgent())->recommend($user->id, 3);

            return Inertia::render('Dashboard', [
                'siswa' => true,
                'profile' => $profile,
                'tracks' => $this->withRubric($tracks),
                'recent' => $recent,
                'recommended' => $recommendation['tasks'],
                'recommendReason' => $recommendation['reason'],
                'activity' => $activity,
            ]);
        }

        // Mitra dashboard
        $topTalents = $pdo->query(
            "SELECT u.id, s.id AS profile_id, u.name, sp.overall_score, sp.badge, sp.tasks_completed
             FROM skill_profiles sp
             JOIN users u ON u.id = sp.user_id
             LEFT JOIN student_profiles s ON s.user_id = sp.user_id
             WHERE sp.tasks_completed > 0 ORDER BY sp.overall_score DESC LIMIT 5"
        )->fetchAll();

        return Inertia::render('Dashboard', [
            'siswa' => false,
            'taskCount' => (int) $pdo->query('SELECT COUNT(*) c FROM tasks')->fetch()['c'],
            'submissionCount' => (int) $pdo->query('SELECT COUNT(*) c FROM submissions')->fetch()['c'],
            'topTalents' => $topTalents,
            'activity' => $activity,
        ]);
    }

    /** Terapkan task_rubric() ke setiap track supaya label kriteria bisa di-render React. */
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
