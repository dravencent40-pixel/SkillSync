<?php
/**
 * SkillSync — Template Konfigurasi Aplikasi
 * Salin file ini menjadi config.php, lalu isi nilai di bawah sesuai environment kamu.
 *   cp config/config.example.php config/config.php
 *
 * config.php TIDAK di-commit ke git (lihat .gitignore) — aman dari kebocoran.
 */

// --- Database -----------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'skillsync');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- Aplikasi -------------------------------------------------------------
define('APP_NAME', 'SkillSync');
// Sesuaikan dengan folder project kamu di XAMPP, contoh: http://localhost/skillsync
define('APP_URL', 'http://localhost/skillsync');

// --- AI Agent (Groq API — gratis, format OpenAI-compatible) ---------------
// Kosongkan GROQ_API_KEY jika belum punya API key: sistem akan otomatis
// memakai mode "Local Heuristic Agent" (rule-based) agar tetap bisa didemokan
// tanpa koneksi internet / biaya API sama sekali.
// Daftar & ambil API key gratis di: https://console.groq.com/keys
putenv('GROQ_API_KEY='); // isi: putenv('GROQ_API_KEY=gsk_xxxx');
define('AI_MODEL', 'llama-3.3-70b-versatile');

// --- Sesi -------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', '1');
