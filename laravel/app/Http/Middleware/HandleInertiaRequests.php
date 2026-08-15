<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();

        $nav = [];
        if ($user) {
            $nav = $user->role === 'siswa'
                ? [
                    ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => in_array($request->route()?->getName(), ['dashboard']), 'icon' => 'M3 10.99 12 4l9 7v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM9 21v-8h6v8'],
                    ['label' => 'Studi Kasus', 'url' => route('tasks'), 'active' => in_array($request->route()?->getName(), ['tasks', 'task.show', 'task.submit', 'submission.show', 'defense.show', 'defense.submit']), 'icon' => 'M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2zM14 2v6h6M16 13H8M16 17H8M10 9H8'],
                    ['label' => 'Profil Skill', 'url' => route('profile'), 'active' => $request->route()?->getName() === 'profile', 'icon' => 'M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z'],
                    ['label' => 'Unggah CV', 'url' => route('cv.create'), 'active' => $request->route()?->getName() === 'cv.create', 'icon' => 'M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12'],
                    ['label' => 'AI Mentor', 'url' => route('mentor'), 'active' => $request->route()?->getName() === 'mentor', 'icon' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'],
                ]
                : [
                    ['label' => 'Dashboard', 'url' => route('dashboard'), 'active' => $request->route()?->getName() === 'dashboard', 'icon' => 'M3 10.99 12 4l9 7v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM9 21v-8h6v8'],
                    ['label' => 'Kelola Task', 'url' => route('tasks'), 'active' => in_array($request->route()?->getName(), ['tasks', 'task.show']), 'icon' => 'M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2zM14 2v6h6'],
                    ['label' => 'Talent Pool', 'url' => route('talent'), 'active' => in_array($request->route()?->getName(), ['talent', 'talent.show']), 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'],
                ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'nav' => $nav,
            'flash' => $request->session()->only(['success', 'error']),
            'aiMode' => ai_mode(),
        ];
    }
}
