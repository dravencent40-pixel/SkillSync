<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CvController extends Controller
{
    public function create(): Response
    {
        $user = Auth::user();

        $stmt = db()->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
        $stmt->execute([$user->id]);

        return Inertia::render('UploadCv', [
            'profile' => $stmt->fetch() ?: ['cv_path' => null, 'cv_original_name' => null, 'cv_uploaded_at' => null],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $pdo = db();
        $check = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
        $check->execute([$user->id]);
        $profile = $check->fetch();

        $errors = [];

        if (!$request->hasFile('cv_file')) {
            $errors[] = 'Pilih file PDF dulu.';
        } else {
            $file = $request->file('cv_file');
            if ($file->getMimeType() !== 'application/pdf') {
                $errors[] = 'Hanya file PDF yang diperbolehkan.';
            } elseif ($file->getSize() > 5 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 5MB.';
            }
        }

        if (!empty($errors)) {
            return back()->withErrors(['form' => implode("\n", $errors)]);
        }

        $uploadDir = public_path('uploads/cvs');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'cv_' . $user->id . '_' . time() . '.pdf';
        $file = $request->file('cv_file');

        if ($profile && $profile['cv_path'] && file_exists(public_path($profile['cv_path']))) {
            @unlink(public_path($profile['cv_path']));
        }

        if (!$file->move($uploadDir, $fileName)) {
            return back()->withErrors(['form' => 'Gagal menyimpan file. Periksa izin direktori uploads/cvs.']);
        }

        $pdo->prepare('UPDATE student_profiles SET cv_path = ?, cv_original_name = ?, cv_uploaded_at = NOW() WHERE user_id = ?')
            ->execute(['uploads/cvs/' . $fileName, $file->getClientOriginalName(), $user->id]);

        log_activity($user->id, 'cv_uploaded', $file->getClientOriginalName());
        session()->flash('success', 'CV berhasil diunggah!');

        return redirect()->route('cv.create');
    }

    public function view(Request $request): BinaryFileResponse|Response
    {
        $user = Auth::user();

        $pdo = db();
        $fileParam = trim((string) $request->query('file', ''));

        // Legacy-style: ?file=cv_{id}_{ts}.pdf — mitra melihat CV dari talent pool
        if ($fileParam !== '') {
            if (!preg_match('/^cv_.+\.pdf$/i', $fileParam)) {
                abort(404);
            }
            $ownerStmt = $pdo->prepare('SELECT user_id FROM student_profiles WHERE cv_path = ?');
            $ownerStmt->execute(['uploads/cvs/' . $fileParam]);
            $ownerId = $ownerStmt->fetchColumn();
            if (!$ownerId) {
                abort(404);
            }
            if ($user->role === 'siswa' && (int) $ownerId !== (int) $user->id) {
                abort(403);
            }
            $path = 'uploads/cvs/' . $fileParam;
        } else {
            // Default: CV milik user yang login
            $stmt = $pdo->prepare('SELECT cv_path FROM student_profiles WHERE user_id = ?');
            $stmt->execute([$user->id]);
            $path = $stmt->fetchColumn();
            if (!$path || !preg_match('/^cv_.+\.pdf$/i', basename($path))) {
                session()->flash('error', 'CV belum diunggah.');
                return Inertia::location(route('cv.create'));
            }
        }

        $full = public_path($path);
        if (!file_exists($full)) {
            abort(404);
        }

        return ResponseFacade::file($full, ['Content-Type' => 'application/pdf']);
    }
}
