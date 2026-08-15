<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TalentController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->input('q', ''));

        $sql = "SELECT sp.user_id, s.id AS profile_id, sp.overall_score, sp.clean_code_avg, sp.security_avg, sp.efficiency_avg,
                       sp.tasks_completed, sp.badge, sp.is_public, sp.strengths, sp.weaknesses,
                       u.name, u.avatar_initial, s.jurusan, s.kelas, s.sekolah
                FROM skill_profiles sp
                JOIN users u ON u.id = sp.user_id
                LEFT JOIN student_profiles s ON s.user_id = sp.user_id
                WHERE sp.is_public = 1 AND sp.tasks_completed > 0";

        $params = [];
        if ($q !== '') {
            $sql .= " AND (u.name LIKE ? OR s.jurusan LIKE ? OR s.sekolah LIKE ?)";
            $params = ["%{$q}%", "%{$q}%", "%{$q}%"];
        }
        $sql .= " ORDER BY sp.overall_score DESC";

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return Inertia::render('Talent', [
            'talent' => $stmt->fetchAll(),
            'q' => $q,
        ]);
    }

    public function show(Request $request, StudentProfile $student): Response|RedirectResponse
    {
        $user = $request->user();

        $stmt = db()->prepare(
            'SELECT sp.*, s.id AS profile_id, u.name, u.avatar_initial, s.jurusan, s.kelas, s.sekolah, s.cv_path
             FROM skill_profiles sp
             JOIN users u ON u.id = sp.user_id
             LEFT JOIN student_profiles s ON s.user_id = sp.user_id
             WHERE sp.user_id = ? AND sp.is_public = 1'
        );
        $stmt->execute([$student->user_id]);
        $profile = $stmt->fetch();

        if (!$profile) {
            session()->flash('error', 'Profil talent tidak ditemukan atau tidak publik.');
            return Inertia::location(route('talent'));
        }

        $tracksStmt = db()->prepare(
            'SELECT spt.*, c.name AS category_name FROM skill_profile_tracks spt
             JOIN task_categories c ON c.id = spt.category_id
             WHERE spt.user_id = ? AND spt.tasks_completed > 0
             ORDER BY spt.tasks_completed DESC, spt.overall_score DESC'
        );
        $tracksStmt->execute([$student->user_id]);
        $tracks = array_map(function ($track) {
            $rubric = task_rubric($track);
            $track['rubric'] = [
                ['score' => $track['criterion1_score'], 'label' => $rubric[0]['label']],
                ['score' => $track['criterion2_score'], 'label' => $rubric[1]['label']],
                ['score' => $track['criterion3_score'], 'label' => $rubric[2]['label']],
            ];
            return $track;
        }, $tracksStmt->fetchAll());

        $recStmt = db()->prepare(
            'SELECT * FROM recommendations WHERE company_id = (SELECT id FROM company_profiles WHERE user_id = ?) AND user_id = ?'
        );
        $recStmt->execute([$user->id, $student->user_id]);
        $recommendation = $recStmt->fetch();

        $histStmt = db()->prepare(
            'SELECT t.title, r.overall_score, s.submitted_at FROM submissions s
             JOIN tasks t ON t.id = s.task_id
             LEFT JOIN ai_reviews r ON r.submission_id = s.id
             WHERE s.user_id = ? ORDER BY s.submitted_at DESC LIMIT 10'
        );
        $histStmt->execute([$student->user_id]);

        return Inertia::render('TalentDetail', [
            'talent' => $profile,
            'tracks' => $tracksStmt->fetchAll(),
            'recommendation' => $recommendation,
            'history' => $histStmt->fetchAll(),
        ]);
    }

    public function recommend(Request $request, StudentProfile $student): RedirectResponse
    {
        $status = $request->input('status');
        if (!in_array($status, ['disimpan', 'dihubungi', 'interview', 'magang'], true)) {
            return back()->withErrors(['form' => 'Status tidak valid.']);
        }

        $note = trim((string) $request->input('note', ''));
        $user = $request->user();

        $pdo = db();
        $companyStmt = $pdo->prepare('SELECT id FROM company_profiles WHERE user_id = ?');
        $companyStmt->execute([$user->id]);
        $companyId = $companyStmt->fetchColumn();

        if (!$companyId) {
            return back()->withErrors(['form' => 'Profil perusahaan belum lengkap.']);
        }

        $pdo->prepare(
            "INSERT INTO recommendations (company_id, user_id, status, note, created_at)
             VALUES (?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status), note = VALUES(note)"
        )->execute([$companyId, $student->user_id, $status, $note]);

        log_activity($user->id, 'talent_recommend', "Talent {$student->user_id} → status {$status}");

        return back();
    }
}
