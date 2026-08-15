<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\SkillProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function login(): Response
    {
        if (Auth::check()) {
            return Inertia::location(route('dashboard'));
        }
        return Inertia::render('Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'is_active' => 1])) {
            throw ValidationException::withMessages(['email' => 'Email atau password salah.']);
        }

        $request->session()->regenerate();
        log_activity(Auth::id(), 'login', null);

        return redirect()->intended(route('dashboard'));
    }

    public function register(): Response
    {
        if (Auth::check()) {
            return Inertia::location(route('dashboard'));
        }
        return Inertia::render('Register');
    }

    public function storeRegister(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required'],
            'email'          => ['required', 'email', 'unique:users,email'],
            'role'           => ['required', 'in:siswa,mitra'],
            'sekolah'        => ['nullable', 'string', 'max:150'],
            'jurusan'        => ['nullable', 'string', 'max:100'],
            'company_name'   => ['required_if:role,mitra'],
            'password'       => ['required', 'min:8'],
            'password_confirm' => ['required', 'same:password'],
        ], [
            'name.required'              => 'Nama lengkap wajib diisi.',
            'email.required'             => 'Email wajib diisi.',
            'email.email'                => 'Email tidak valid.',
            'email.unique'               => 'Email ini sudah terdaftar. Coba masuk, atau gunakan email lain.',
            'role.required'              => 'Pilih peran akun.',
            'company_name.required_if'   => 'Nama perusahaan wajib diisi untuk akun Mitra.',
            'password.required'          => 'Password wajib diisi.',
            'password.min'               => 'Password minimal 8 karakter.',
            'password_confirm.required'  => 'Konfirmasi password wajib diisi.',
            'password_confirm.same'      => 'Konfirmasi password tidak cocok.',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'           => $data['name'],
                'email'          => $data['email'],
                'password_hash'  => Hash::make($data['password']),
                'role'           => $data['role'],
                'avatar_initial' => initials($data['name']),
            ]);

            if ($data['role'] === 'siswa') {
                StudentProfile::create([
                    'user_id' => $user->id,
                    'sekolah' => $data['sekolah'] ?: null,
                    'jurusan' => $data['jurusan'] ?: null,
                ]);
                SkillProfile::create(['user_id' => $user->id, 'badge' => 'Pemula']);
            } else {
                CompanyProfile::create([
                    'user_id'      => $user->id,
                    'company_name' => $data['company_name'],
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        log_activity($user->id, 'register', "Role: {$data['role']}");

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->flash('success', 'Kamu telah keluar.');

        return redirect()->route('login');
    }
}
