<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DefenseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\TalentController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

// ---- Publik -------------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/login', [AuthController::class, 'login'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'store'])->middleware('guest');
Route::get('/register', [AuthController::class, 'register'])->middleware('guest');
Route::post('/register', [AuthController::class, 'storeRegister'])->middleware('guest');

// ---- Semua user login ---------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks');
    Route::get('/task/{task}', [TaskController::class, 'show'])->name('task.show');

    Route::get('/submission/{submission}', [SubmissionController::class, 'show'])->name('submission.show');
    Route::get('/defense/{submission}', [DefenseController::class, 'show'])->name('defense.show');
    Route::get('/view-cv', [CvController::class, 'view'])->name('cv.view');
});

// ---- Khusus Siswa -------------------------------------------------------
Route::middleware(['auth', 'role:siswa'])->group(function () {
    Route::post('/task/{task}', [TaskController::class, 'submit'])->name('task.submit');
    Route::post('/defense', [DefenseController::class, 'submit'])->name('defense.submit');
    Route::get('/mentor', [MentorController::class, 'index'])->name('mentor');
    Route::post('/mentor/send', [MentorController::class, 'send'])->name('mentor.send');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'togglePublic'])->name('profile.toggle');
    Route::get('/upload-cv', [CvController::class, 'create'])->name('cv.create');
    Route::post('/upload-cv', [CvController::class, 'store'])->name('cv.store');
});

// ---- Khusus Mitra -------------------------------------------------------
Route::middleware(['auth', 'role:mitra'])->group(function () {
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/talent', [TalentController::class, 'index'])->name('talent');
    Route::get('/talent/{student}', [TalentController::class, 'show'])->name('talent.show');
    Route::post('/talent/{student}', [TalentController::class, 'recommend'])->name('talent.recommend');
});
