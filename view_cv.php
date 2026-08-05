<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$file = basename((string) ($_GET['file'] ?? ''));
if ($file === '' || strpos($file, 'cv_') !== 0 || substr($file, -4) !== '.pdf') {
    http_response_code(400);
    exit('File tidak valid.');
}

$uploadsDir = realpath(__DIR__ . '/uploads/cvs');
$full = realpath($uploadsDir . '/' . $file);

if ($full === false || $uploadsDir === false || strpos($full, $uploadsDir) !== 0 || !is_file($full)) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

$user = current_user();
if ($user['role'] === 'siswa') {
    $pdo = db();
    $check = $pdo->prepare('SELECT cv_path FROM student_profiles WHERE user_id = ?');
    $check->execute([$user['id']]);
    if ($check->fetchColumn() !== 'uploads/cvs/' . $file) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $file . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($full);
exit;
