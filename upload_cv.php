<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$uploadsDir = __DIR__ . '/uploads/cvs';
$metaFile = $uploadsDir . '/metadata.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name) || empty($email) || empty($_FILES['cv_file']['name'])) {
        flash('error', 'Isi nama lengkap, email, dan pilih file CV.');
        redirect('upload_cv.php');
    }
    if (strlen($name) < 5) {
        flash('error', 'Nama lengkap minimal 5 karakter.');
        redirect('upload_cv.php');
    }
    if (strlen($email) < 5) {
        flash('error', 'Email minimal 5 karakter.');
        redirect('upload_cv.php');
    }

    $file = $_FILES['cv_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Gagal mengunggah file. Coba lagi.');
        redirect('upload_cv.php');
    }

    $allowed = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($finfo, $file['tmp_name']);
      finfo_close($finfo);
    } else {
      $mime = $file['type'] ?? null;
    }

    if (!in_array($mime, $allowed)) {
      flash('error', 'Hanya file PDF / DOC / DOCX yang diperbolehkan.');
      redirect('upload_cv.php');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        flash('error', 'Ukuran file maksimal 5MB.');
        redirect('upload_cv.php');
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $dest = $uploadsDir . '/' . $basename;

    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('error', 'Gagal menyimpan file. Periksa izin direktori.');
        redirect('upload_cv.php');
    }

    $meta = [];
    if (file_exists($metaFile)) {
        $meta = json_decode(file_get_contents($metaFile), true) ?: [];
    }
    $entry = [
        'id' => bin2hex(random_bytes(6)),
        'name' => $name,
        'email' => $email,
        'filename' => $basename,
        'original' => $file['name'],
        'mime' => $mime,
      'thumb' => null,
        'size' => $file['size'],
        'uploaded_at' => date('c')
    ];
    $meta[] = $entry;
    if ($mime === 'application/pdf') {
      $thumbDir = $uploadsDir . '/thumbs';
      if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);
      $thumbName = pathinfo($basename, PATHINFO_FILENAME) . '.png';
      $thumbPath = $thumbDir . '/' . $thumbName;

      $thumbCreated = false;

      if (class_exists('Imagick')) {
        try {
          $imagick = new Imagick();
          $imagick->setResolution(150, 150);
          $imagick->readImage($dest . '[0]');
          $imagick->setImageFormat('png');
          $imagick->setImageColorspace(
            defined('Imagick::COLORSPACE_SRGB') ? Imagick::COLORSPACE_SRGB : 1
          );
          $imagick->writeImage($thumbPath);
          $imagick->clear();
          $imagick->destroy();
          $thumbCreated = file_exists($thumbPath);
        } catch (Exception $e) {}
      }

      if (!$thumbCreated) {
        $pdftoppm = trim(shell_exec('which pdftoppm 2>/dev/null')) ?: null;
        if ($pdftoppm) {
          $cmd = escapeshellcmd($pdftoppm) . ' -singlefile -png ' . escapeshellarg($dest) . ' ' . escapeshellarg($thumbDir . '/' . pathinfo($basename, PATHINFO_FILENAME));
          @exec($cmd);
          if (file_exists($thumbPath)) $thumbCreated = true;
        }
      }

      if ($thumbCreated) {
        $meta[count($meta)-1]['thumb'] = 'uploads/cvs/thumbs/' . $thumbName;
      }
    }

    file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    flash('success', 'CV berhasil diunggah. Menampilkan pratinjau...');
    redirect('upload_cv.php?preview=' . rawurlencode($basename));
}

$cvList = [];
if (file_exists($metaFile)) {
    $cvList = json_decode(file_get_contents($metaFile), true) ?: [];
}

$pageTitle = 'Upload CV';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-4xl mx-auto px-6 py-10">
  <div class="animate-fade-up">
    <h1 class="text-2xl md:text-3xl font-bold tracking-tight">Unggah CV Kamu</h1>
    <p class="mt-2 text-sm text-[var(--muted)] max-w-lg">Bagikan CV dalam format PDF/DOC/DOCX. Pratinjau otomatis muncul jika file berupa PDF.</p>
  </div>

  <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Upload Form -->
    <div class="lg:col-span-2 animate-fade-up" style="animation-delay: 0.1s;">
      <div class="surface p-8 rounded-3xl">
        <form method="POST" enctype="multipart/form-data" class="space-y-5">
          <div>
            <label for="name">Nama Lengkap <span class="text-[var(--muted-light)] font-normal">(minimal 3 karakter)</span></label>
            <input type="text" id="name" name="name" required minlength="3" placeholder="Masukkan nama kamu">
          </div>
          <div>
            <label for="email">Email <span class="text-[var(--muted-light)] font-normal">(minimal 3 karakter)</span></label>
            <input type="email" id="email" name="email" required minlength="3" placeholder="nama@email.com">
          </div>
          <div>
            <label>File CV</label>
            <div id="dropZone" class="file-input-custom cursor-pointer">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
              <div class="flex-1">
                <p class="text-sm font-medium text-[var(--ink)]" id="dropLabel">Pilih file atau seret ke sini</p>
                <p class="text-xs text-[var(--muted)]">PDF, DOC, DOCX &middot; Maks 5MB</p>
              </div>
              <input type="file" name="cv_file" accept=".pdf,.doc,.docx" required id="fileInput">
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-full py-3">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
            Unggah CV
          </button>
        </form>
      </div>
    </div>

    <!-- Tips -->
    <div class="animate-fade-up" style="animation-delay: 0.2s;">
      <div class="surface p-6 rounded-2xl sticky top-24">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: #f5f5f5; color: #0a0a0a;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
          </div>
          <h3 class="text-sm font-bold">Tips Cepat</h3>
        </div>
        <ul class="space-y-3 text-sm text-[var(--muted)]">
          <li class="flex items-start gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="2" class="shrink-0 mt-0.5"><polyline points="20 6 9 17 4 12"/></svg>
            Gunakan PDF untuk pratinjau langsung.
          </li>
          <li class="flex items-start gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="2" class="shrink-0 mt-0.5"><polyline points="20 6 9 17 4 12"/></svg>
            Pastikan ukuran di bawah 5MB.
          </li>
          <li class="flex items-start gap-2">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0a0a0a" stroke-width="2" class="shrink-0 mt-0.5"><polyline points="20 6 9 17 4 12"/></svg>
            Hapus data sensitif sebelum mengunggah.
          </li>
        </ul>
      </div>
    </div>
  </div>

  <?php if (!empty($_GET['preview'])):
    $previewFile = basename($_GET['preview']);
    $meta = [];
    if (file_exists($metaFile)) { $meta = json_decode(file_get_contents($metaFile), true) ?: []; }
    $entry = null;
    foreach ($meta as $m) { if ($m['filename'] === $previewFile) { $entry = $m; break; } }
  ?>
    <div class="mt-10 animate-fade-up">
      <?php if ($entry): ?>
        <div class="surface rounded-3xl p-6">
          <div class="flex items-start gap-6">
            <div class="flex-1">
              <p class="text-xs text-[var(--muted)]">Unggah oleh</p>
              <h2 class="text-lg font-semibold mt-1"><?= e($entry['name']) ?></h2>
              <p class="text-xs text-[var(--muted-light)] mt-1"><?= e($entry['original']) ?> &middot; <?= date('d M Y H:i', strtotime($entry['uploaded_at'])) ?></p>
            </div>
            <a href="uploads/cvs/<?= rawurlencode($entry['filename']) ?>" class="btn btn-dark btn-sm" target="_blank">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" x2="21" y1="14" y2="3"/></svg>
              Buka di tab baru
            </a>
          </div>

          <div class="mt-6">
            <?php if ($entry['mime'] === 'application/pdf'): ?>
              <div class="preview-frame rounded-2xl overflow-hidden border border-[var(--border)]">
                <iframe src="uploads/cvs/<?= rawurlencode($entry['filename']) ?>" style="width:100%;height:650px;border:0;" title="Pratinjau CV"></iframe>
              </div>
            <?php else: ?>
              <p class="text-sm text-[var(--muted)]">Pratinjau hanya tersedia untuk PDF. <a href="uploads/cvs/<?= rawurlencode($entry['filename']) ?>" class="link-accent">Unduh file</a></p>
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="surface rounded-3xl p-8 text-center">
          <p class="text-[var(--muted)]">File pratinjau tidak ditemukan.</p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const dropLabel = document.getElementById('dropLabel');

fileInput.addEventListener('change', function() {
  if (this.files.length > 0) {
    dropLabel.textContent = this.files[0].name;
    dropZone.style.borderColor = '#0a0a0a';
    dropZone.style.background = '#f5f5f5';
  }
});

dropZone.addEventListener('dragover', function(e) {
  e.preventDefault();
  this.classList.add('dragover');
});

dropZone.addEventListener('dragleave', function(e) {
  e.preventDefault();
  this.classList.remove('dragover');
});

dropZone.addEventListener('drop', function(e) {
  e.preventDefault();
  this.classList.remove('dragover');
  if (e.dataTransfer.files.length > 0) {
    fileInput.files = e.dataTransfer.files;
    dropLabel.textContent = e.dataTransfer.files[0].name;
    this.style.borderColor = '#0a0a0a';
    this.style.background = '#f5f5f5';
  }
});
</script>

<?php require __DIR__ . '/includes/footer.php';
