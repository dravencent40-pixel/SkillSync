<?php
/**
 * SkillSync — Alat Diagnosa Koneksi Groq API
 *
 * Buka halaman ini langsung di browser (mis. http://localhost/skillsync/ai-test.php)
 * setelah login untuk melihat PERSIS kenapa Reviewer/Mentor jatuh ke mode heuristik
 * padahal API key sudah diisi. Paling sering: masalah SSL certificate bawaan XAMPP
 * di Windows (curl tidak menemukan CA bundle untuk verifikasi HTTPS).
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/agents/AIClient.php';
require_login();

$client = new AIClient();
$testResult = null;
$ran = false;

if (isset($_GET['run'])) {
    $ran = true;
    $testResult = $client->complete(
        'Kamu asisten uji koneksi. Balas HANYA dengan kata: OK',
        [['role' => 'user', 'content' => 'ping']],
        20
    );
}

$maskedKey = '';
$rawKey = getenv('GROQ_API_KEY') ?: '';
if ($rawKey !== '') {
    $maskedKey = substr($rawKey, 0, 10) . str_repeat('•', max(0, strlen($rawKey) - 14)) . substr($rawKey, -4);
}

$curlVersion = extension_loaded('curl') ? curl_version() : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Diagnosa Koneksi AI — SkillSync</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>body{font-family:-apple-system,Segoe UI,Roboto,sans-serif;background:#fafafa;}</style>
</head>
<body class="p-6 md:p-12 max-w-3xl mx-auto">

  <h1 class="text-2xl font-bold mb-1">Diagnosa Koneksi Groq API</h1>
  <p class="text-sm text-neutral-500 mb-8">Alat bantu — hapus/lindungi file ini sebelum deploy ke publik.</p>

  <div class="bg-white border border-neutral-200 rounded-2xl p-6 mb-6">
    <h2 class="font-semibold mb-3">1. Status Konfigurasi</h2>
    <table class="text-sm w-full">
      <tr class="border-b border-neutral-100"><td class="py-2 text-neutral-500 w-56">API key terdeteksi?</td>
        <td class="py-2 font-medium"><?= $client->isAvailable() ? '✅ Ya' : '❌ Tidak — cek putenv() di config/config.php' ?></td></tr>
      <?php if ($maskedKey): ?>
      <tr class="border-b border-neutral-100"><td class="py-2 text-neutral-500">Preview key</td>
        <td class="py-2 font-mono text-xs"><?= e($maskedKey) ?></td></tr>
      <?php endif; ?>
      <tr class="border-b border-neutral-100"><td class="py-2 text-neutral-500">Model dipakai</td>
        <td class="py-2 font-mono text-xs"><?= e(defined('AI_MODEL') ? AI_MODEL : '(default)') ?></td></tr>
      <tr class="border-b border-neutral-100"><td class="py-2 text-neutral-500">Ekstensi curl aktif?</td>
        <td class="py-2 font-medium"><?= extension_loaded('curl') ? '✅ Ya' : '❌ Tidak — aktifkan php_curl di php.ini' ?></td></tr>
      <tr><td class="py-2 text-neutral-500">curl SSL backend</td>
        <td class="py-2 font-mono text-xs"><?= e($curlVersion['ssl_version'] ?? 'ekstensi curl tidak aktif') ?></td></tr>
    </table>
  </div>

  <div class="bg-white border border-neutral-200 rounded-2xl p-6 mb-6">
    <h2 class="font-semibold mb-3">2. Tes Panggilan Nyata ke Groq</h2>
    <?php if (!$ran): ?>
      <p class="text-sm text-neutral-500 mb-4">Klik tombol untuk mengirim satu pesan uji ke Groq API dan lihat hasilnya persis apa adanya.</p>
      <a href="?run=1" class="inline-block px-4 py-2 rounded-lg bg-black text-white text-sm font-medium">Jalankan Tes</a>
    <?php else: ?>
      <?php if ($testResult !== null): ?>
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
          ✅ <strong>Berhasil!</strong> Groq membalas: <code><?= e($testResult) ?></code><br>
          Reviewer &amp; Mentor seharusnya sudah pakai Groq sekarang. Kalau di halaman lain masih heuristik, cek apakah kamu login/refresh setelah menyimpan API key.
        </div>
      <?php else: ?>
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
          ❌ <strong>Gagal.</strong> Detail teknis:
          <pre class="mt-2 whitespace-pre-wrap text-xs bg-white/60 p-3 rounded-lg border border-red-100"><?= e($client->lastError()) ?></pre>
        </div>
        <p class="mt-4 text-sm text-neutral-500"><a href="?run=1" class="underline">Coba lagi</a></p>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="bg-white border border-neutral-200 rounded-2xl p-6">
    <h2 class="font-semibold mb-3">3. Perbaikan Paling Umum (XAMPP Windows)</h2>
    <div class="text-sm text-neutral-700 space-y-4">
      <div>
        <p class="font-medium">Kalau error menyebut "SSL certificate problem" atau "unable to get local issuer certificate" (errno 60):</p>
        <ol class="list-decimal ml-5 mt-1 space-y-1 text-neutral-600">
          <li>Download <code>cacert.pem</code> dari <a class="underline" href="https://curl.se/ca/cacert.pem" target="_blank">curl.se/ca/cacert.pem</a>, simpan mis. di <code>C:\xampp\php\extras\cacert.pem</code>.</li>
          <li>Buka <code>C:\xampp\php\php.ini</code>, cari baris <code>;curl.cainfo=</code>, ubah jadi (hapus titik-koma di depan):<br>
              <code>curl.cainfo = "C:\xampp\php\extras\cacert.pem"</code></li>
          <li>Cari juga <code>;openssl.cafile=</code>, lakukan hal sama.</li>
          <li>Restart Apache dari XAMPP Control Panel.</li>
          <li>Refresh halaman ini, klik "Jalankan Tes" lagi.</li>
        </ol>
      </div>
      <div>
        <p class="font-medium">Kalau error "401 Unauthorized" atau "Invalid API Key":</p>
        <p class="text-neutral-600">API key salah/kadaluarsa/kepotong saat copy-paste. Generate key baru (gratis) di
          <a class="underline" href="https://console.groq.com/keys" target="_blank">console.groq.com/keys</a>,
          pastikan tidak ada spasi di awal/akhir saat ditempel ke <code>config/config.php</code>.</p>
      </div>
      <div>
        <p class="font-medium">Kalau error "Could not resolve host" atau timeout:</p>
        <p class="text-neutral-600">Koneksi internet server/laptop kamu tidak bisa menjangkau <code>api.groq.com</code> — cek firewall/antivirus/proxy kampus.</p>
      </div>
      <div>
        <p class="font-medium">Kalau error "429 Too Many Requests":</p>
        <p class="text-neutral-600">Kena limit free tier Groq (per menit/hari). Tunggu sebentar lalu coba lagi, atau kurangi frekuensi panggilan saat demo.</p>
      </div>
    </div>
  </div>

  <p class="text-xs text-neutral-400 mt-8">⚠️ Hapus file <code>ai-test.php</code> ini (atau batasi akses) sebelum project di-deploy untuk umum — halaman ini membocorkan sebagian info konfigurasi.</p>

</body>
</html>
