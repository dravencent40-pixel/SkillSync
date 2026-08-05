# SkillSync — Session Memory

## Objective
Meng-upgrade & memperbaiki keseluruhan UI aplikasi SkillSync (`E:\laragon\www\skillsync`) sambil mempertahankan struktur sistem PHP + MySQL, dan menghindari tampilan "AI slop design" sesuai pedoman skill desain yang ter-install.

## Rename brand (sesi terbaru)
- Semua "SkillSync AI" → **"SkillSync"** (brand halaman, `<title>`, footer, prompt agent, komentar kode, README).
- **Database MySQL `skillsync_ai` di-rename menjadi `skillsync`** (migrasi via PHP PDO: 15 tabel disalin struktur+data, count diverifikasi, DB lama di-drop). `DB_NAME` di `config/config.php` & `config.example.php`, `setup.php`, `verify.sql`, header SQL migrasi ikut diupdate. Koneksi terverifikasi (3 users, 6 submissions, 15 tabel).
- KECUALI yang TIDAK diubah: URL repo GitHub `https://github.com/dravencent40-pixel/SkillSync-AI.git` di README (nama repo asli — mengubahnya merusak perintah clone).
- Tool migrasi sementara: `%TEMP%\opencode\rename_db.php`, `dbcheck.php` (bukan bagian project).

## Important Details
- Sistem design baru sudah diputuskan & mulai dieksekusi: **paper-first technical-editorial** — palet kertas hangat (`--paper: #f7f6f2`, `--surface: #fff`, `--ink: #1b1a16`, `--muted: #5f5e56`, `--muted-light: #8b897f`, `--faint: #b4b2a7`, border `#e7e5dd`), aksen hijau forest tunggal (`--accent: #167a56`, `--accent-dark: #0e5c40`, `--accent-deep: #0a4731`, `--accent-light: #1f9c6e`, `--accent-50: #eef7f2`, `--accent-100: #d9efe2`, `--accent-200: #b5dfc7`, `--accent-ring`), JetBrains Mono untuk data (`font-feature-settings 'tnum'`), shadow lembut, radius token (`--radius-*`), transisi `--ease: cubic-bezier(0.16,1,0.3,1)`, `prefers-reduced-motion` support. **Tidak ada biru/gradient biru, tidak glassmorphism, tidak rainbow icon tiles, tidak rounded-3xl berlebihan.**
- Tampilan sebelumnya: accent `#2563eb`, ink `#0a0a0a`, muted `#525252`, gradient `--gradient-dark` tetap dipakai untuk welcome banner & section statistik gelap.
- Flag anti-slop di CSS baru: larangan font generik hanya untuk Inter/Roboto/Arial/Open Sans/Helvetica — pakai Outfit + JetBrains Mono; kelas `.mode-badge.online .dot` memakai animasi ping; `.mini-bar` animasi `growBar`; `.site-header.scrolled` untuk shadow saat scroll (bukan box-shadow inline lama).
- Kelas yang **tetap wajib dipertahankan** karena JS/halaman memakainya: `#hamburgerBtn`, `#mobileMenu`, `.spot-card` (kini memakai CSS vars `--mx`/`--my` — `app.js` sudah diedit), `.score-ring` (`circle.progress` + `dataset.score`), `.blob`, `.mesh-bg`, `.empty-state`, `.btn-*`, `.surface`, `.avatar-*`, `.badge-*`, `.animate-fade-up`, `.animate-float`, `.code-editor`, `.bubble-agent`, `.bubble-user`, `.modal-card`, `.modal-overlay` (tidak lagi `hidden` — memakai `.open` class), `.file-input-custom`, `.role-option`, `.welcome-banner`, `.stat-card`, `.nav-link`, `.hamburger` (memakai `.open` bukan `.active` — `app.js` diedit), `.mobile-menu.open`, `.modal-close`, `.cv-card`.
- Token CSS tersedia: `--paper-soft: #f0efe9`, `--border-strong: #d6d4c9`, `--danger: #c1382f`, `--danger-50: #fbefed`, `--warning: #b45309`, `--warning-50: #fdf3e3`, `--gradient-accent`, `--gradient-dark`, `--gradient-mesh`, `--shadow-accent`. **TIDAK ada var `--success`** (jangan dipakai).
- `.surface` radius = `--radius-2xl` (1.375rem). Kelas `rounded-3xl` = 1.75rem, hanya dipakai utk panel besar yang disengaja (login panel kiri, CTA index).
- Struktur sistem: 5 agent (Task Issuer, Reviewer & Auditor, Defense, Mentor, Profile Generator), skor per divisi, Talent Pool, CSRF, mode AI (Groq vs heuristic lokal).
- Demo credentials: `rafi@smkn9bekasi.sch.id` / `admin@goodeva.tech` / password `password123`.
- Dev utilities `ai-test.php` & `database/setup.php` sengaja TIDAK diubah styling-nya (standalone, pakai Tailwind CDN sendiri, warnanya slate/neutral; file dev tool yang sebaiknya dihapus sebelum deploy).
- **Database local (`skillsync`) ternyata outdated** — halaman fatal (dashboard/profile/defense/task/talent) karena tabel/kolom baru hilang. Sudah diperbaiki dengan migrasi berikut (semua pakai guard `IF EXISTS`/`SELECT information_schema`):
  - `CREATE TABLE skill_profile_tracks` (dari `database/migrate_track_scores.sql`).
  - `ALTER TABLE skill_profiles ADD comprehension_avg` (dari `database/migrate_defense.sql` — **PERHATIAN: `ADD COLUMN IF NOT EXISTS` gagal di MySQL 8, hanya didukung MariaDB; pakai guard query**).
  - `CREATE TABLE defense_sessions`, `CREATE TABLE defense_questions`.
  - `student_profiles` + `cv_path`, `cv_original_name`, `cv_uploaded_at`.
  - `task_categories` + `submission_type` (ENUM code/design/network/general), `rubric_criteria` (JSON).
  - `submissions` + `file_path`, `external_link`.

## Work State
### Completed
- 7 skill design taste ter-install; `README.md` sudah direvisi total; audit UI sebelumnya sudah selesai.
- **`assets/css/style.css`** — design system baru lengkap (token kertas + hijau, form/button/surface/badge/avatar/code-editor/header/nav/modal/file-input/role-option/score-ring/timeline/skeleton/mini-bar/animasi; `.btn-primary` gradient hijau, `.btn-dark` ink, `.btn-ghost` border kertas, `.welcome-banner` gradient dark + mesh). Sudah ditulis ulang penuh.
- **`assets/js/app.js`** — hamburger `.open`, spotlight `--mx/--my`, scroll header toggle class `.scrolled`.
- **`includes/header.php`** & **`includes/footer.php`** — logo-tile, favicon hijau, tailwind config (ink/accent/radius), mode badge, mobile menu, flash, modal CV.
- Halaman yang sudah diedit & lolos `php -l`: `index.php`, `login.php`, `register.php`, `dashboard.php` (siswa & mitra), `tasks.php`, `task.php`, `submission.php`, `defense.php`, `mentor.php`, `profile.php`, `upload_cv.php`, `company/talent.php`, `company/talent-detail.php`, `includes/activity_timeline.php`.
- `profile.php` — avatar `gradient-accent`, skor per divisi pakai `.mini-bar` + stagger, kekuatan/warning icon tile pakai `--accent-50`/`--warning-50`, history pakai score badge accent/danger + `.num`.
- `upload_cv.php` — error banner pakai `--danger-50`/`--danger`, file card `--accent-50`, form surface default.
- `company/talent.php` — fix `var(--success)` (tidak ada) → `var(--accent)`; empty state tanpa `rounded-3xl`.
- `company/talent-detail.php` — avatar `gradient-accent`, score ring solid accent (hapus defs blue gradient `#talentGradient`), skor per divisi mini-bar, narrative label `--accent-200` + teks `#e8e6dd`, simpan status `btn-primary`, history sama seperti profile.
- `includes/activity_timeline.php` — icon tile `--paper-soft` + `--ink`, hapus `rounded-3xl`.
- `rounded-3xl` berlebih dihapus dari empty-state `tasks.php`/`dashboard.php`/`submission.php`.
- Scan repo: tidak ada lagi `bg-slate/neutral/red-50`, `#3b82f6`, `#2563eb`, `#0a0a0a`, `#525252`, `#f5f5f5`, `badge-info`, `btn-dark` di halaman app (kecuali dev utilities).

### Active
- (none) — semua halaman app sudah seragam design system baru.

### Blocked
- (none) — tidak ada blocker aktif.
- **Groq API key VALID** (user update, HTTP 200 `llama-3.3-70b-versatile`). `GROQ_API_KEY` di-set via `putenv()` di `config/config.php:23` — untuk cek ketersediaan harus `require config.php` dulu sebelum `new AIClient()`.
- **Cache-buster**: `<link stylesheet>/assets/css/style.css?v=2` (header.php) & `app.js?v=2` (footer.php) — user sempat lihat tampilan lama karena cache browser; hard refresh (Ctrl+Shift+R) menyelesaikan. Naikkan `?v=` tiap ubah CSS/JS.

## Demo Data (seeded via real pipeline)
- **`database/seed_demo.php`** (baru, re-runnable): mereplikasi pipeline asli dari `task.php`/`defense.php` — insert submission → `ReviewerAuditorAgent->review()` → insert ai_reviews → `DefenseAgent->generateQuestions()` → sesi defense → simulasi jawab → `evaluateAnswers()` → `ProfileGeneratorAgent->regenerate()`. Menghapus submission demo lama user target sebelum seed (cascade). Jalankan: `php database/seed_demo.php`.
- **Jawaban defense Rafi ditulis oleh AI** (`$aiClient->completeJson`) terhadap pertanyaan spesifik sesi (1 panggilan per task, 4 pertanyaan → array JSON); Sinta memakai jawaban canned pendek. Ini membuat evaluator strict memberi skor wajar: rafi tinggi, sinta rendah.
- **Hasil seed terakhir (mode Groq penuh)**: rafi review 90/87/85, comprehension 88/85/94; sinta review 39/42/56, comprehension 0/20/30. Tracks: rafi Data&Backend 86 / WebDev 89 (overall 88, badge Job Ready), sinta 35/38 (overall 37, badge Pemula). Narrative rafi menyebut skor pemahaman 89/100.
- **Catatan penting**: DefenseAgent selalu generate **4 pertanyaan**; jawaban canned lama hanya 3 sehingga membungkus (duplikat) & jawaban tidak sesuai pertanyaan → nilai 0-40. Jika tidak pakai AI-answer, pastikan jumlah jawaban = jumlah pertanyaan & relevan.
- **Penting: `submission` AUTO_INCREMENT sudah di 36+** — id submission nyata sekarang 31-36 (rafi 31-33, sinta 34-36); cek via `SELECT id FROM submissions` sebelum testing.
- Heuristik reviewer lenient: hanya deteksi pola (prepared stmt, N+1 via loop+query, interpolasi variabel ke string SQL tanpa nested quote). Untuk diferensiasi kualitas nyata pakai Groq (key sudah valid).

## Verified (smoke test selesai)
- 31 file PHP lolos `php -l` (0 gagal).
- Server `http://localhost/skillsync` hidup; CSS/JS asset 200 dengan MIME benar; halaman terproteksi 302 → login.
- Login siswa (`rafi@smkn9bekasi.sch.id`) & mitra (`admin@goodeva.tech`, password `password123`) berhasil.
- Crawl auth (semua 200, tanpa Fatal/Warning/Notice/Deprecated; match `Warning` hanya false-positive `var(--warning)` token & label badge): siswa — dashboard, tasks, task?id=1..3, submission?id=31, defense?submission_id=31, mentor, profile, upload_cv; mitra — talent.php (2 kartu), talent-detail?id=2&3. `logout.php` 302 OK.
- **API chat mentor (`api/chat.php`) diverifikasi**: unauth → 401 JSON, auth + body JSON valid → 200 dengan balasan Groq bahasa Indonesia (prepared statement). Catatan: body JSON dari PowerShell harus via file (`--data-binary @file`), string `-d '...'` rentan rusak.
- `register.php` publik 200.
- Setelah seed: dashboard menampilkan `.mini-bar` (Web Dev 90%, Data&Backend 88%), submission#31 (rafi) badge AI Judgment + skor 90 + feedback Groq, defense#31 score-ring 88 + narasi AI + Q&A per pertanyaan, profile overall 88 Job Ready + narrative, talent pool menampilkan rafi 88 (Job Ready) & sinta 37 (Pemula), talent-detail narrative + score-ring + recruitment form. Semua marker design system OK.
- Token design baru terverifikasi ada di CSS & halaman render (`--accent: #167a56`, `--gradient-accent`, `.mini-bar`, `.mode-badge`, `.site-header`, `badge-accent`, `num`, `score-ring`). `mini-bar` di dashboard kosong hanya karena `skill_profile_tracks` baru dibuat & belum ada data (expected).

## Next Move
1. **Commit** pekerjaan UI upgrade + seed + cache-buster + security hardening (belum ada commit untuk perubahan ini).
2. Upload CV asli via UI (`upload_cv.php`) untuk demo talent pool lebih lengkap (flow sudah teruji via proxy `view_cv.php`).
3. (opsional) Cek `database/setup.php` memakai `◎` — tidak perlu diubah.

## Security & UX (sesi lanjutan)
- **"Unggah CV" dipindah**: footer (publik) → nav siswa (desktop+mobile, header.php) + tombol di header profile.php; footer kini berisi Talent Pool/Masuk/Daftar.
- **index.php CTA diperbaiki**: "Daftar Sekarang"/"Mulai Sekarang" → `register.php` (sebelumnya `upload_cv.php` yang khusus siswa — CTA publik rusak).
- **Proteksi `.htaccess`** (semua 403 terverifikasi): `config/`, `database/`, `includes/`, `uploads/cvs/` = `Require all denied`; root blokir `ai-test.php`, `composer.*`, dotfiles + `Options -Indexes`.
- **`view_cv.php` (baru)** — proxy CV wajib login: mitra dapat akses semua, siswa hanya CV milik sendiri; validasi `basename()` + `realpath` containment (path traversal → 400); header `X-Content-Type-Options: nosniff`, `Cache-Control: no-store`. `upload_cv.php` & `company/talent-detail.php` kini memakai `view_cv.php?file=<nama>`.
- Catatan uji: body JSON curl via `--data-binary @file` (string `-d '...'` di PowerShell mudah rusak).

## Layout fix (feedback user: teks keluar frame)
- **`.cv-card` TANPA padding** → konten menempel border kartu (talent pool). Fix: `padding: 1.25rem` + `overflow: hidden` di CSS.
- **Kartu skor (ring+teks)** tanpa `min-w-0` di div teks → area teks menyempit & terpotong/clipped oleh `overflow:hidden` di 1024px. Fix `min-w-0` di `dashboard.php`, `profile.php`, `company/talent-detail.php`; label diberi `leading-snug`.
- **Baris list** (recent submissions, top talent, history) div teks diberi `min-w-0` agar judul/teks panjang mengecil-bungkus, tidak mendorong frame (dashboard.php ×2, profile.php, talent-detail.php).
- **`.badge`** diberi `max-width:100%; overflow:hidden; text-overflow:ellipsis` (safety net, tidak bisa blow-out frame).
- Verifikasi otomatis puppeteer (768/900/1024/1280/1440px, siswa+mitra+dashboard+talent pool+talent-detail+profile): **0 overflow** (sebelumnya talent-detail score card over padding 10px, cv-card tak berpadding).
- CSS cache-buster naik ke `?v=4` (2× ubah CSS sejak v2: `.cv-card`, lalu banner).
- **Bug banner "Dashboard Mitra" terpotong** (ditemukan via probe geometri puppeteer + cookie):
  - `.welcome-banner` **tidak punya padding** → konten menempel tepi rounded-corner (`overflow:hidden` + radius 24px) → teks "Dashboard Mitra" terpotong kurva sudut. Fix: `padding: 2.25rem 1.5rem; @md: 3rem 3.25rem` di CSS (berlaku utk banner siswa & mitra).
  - **Typo class `max-w-7x1`** di dashboard.php (baris ~222) → section tanpa max-width, banner melebar full (1864px @1920px). Fix → `max-w-7xl mx-auto px-6` (disamakan dgn siswa). Setelah fix: banner terkunci 1232px, label berjarak 48px dari tepi.
  - Verifikasi ulang penuh 768–1920px (6 halaman, siswa+admin): **30/30 bersih**, 0 overflow teks.
- Login utk testing via puppeteer sekarang pakai curl CSRF flow (get token → POST) + cookie jar (`cookies.txt` di temp pptr); `_shot.php` TIDAK dibuat ulang.
- **CATATAN PENTING**: untuk debugging layout sempat dibuat `_shot.php` (bypass login via session) — SUDAH DIHAPUS dari webroot. Jangan pernah buat file sejenis; kalau perlu screenshot pakai login curl + Chrome headless dengan cookie.

## Relevant Files
- `E:\laragon\www\skillsync\assets\css\style.css`: design system baru lengkap (582 baris).
- `E:\laragon\www\skillsync\assets\js\app.js`: sudah disesuaikan (`.open`, `--mx/--my`, `.scrolled`).
- `E:\laragon\www\skillsync\includes\header.php` & `includes\footer.php`: sudah diedit.
- Semua halaman app (`index`, `login`, `register`, `dashboard`, `tasks`, `task`, `submission`, `defense`, `mentor`, `profile`, `upload_cv`, `company\talent`, `company\talent-detail`, `includes\activity_timeline`): sudah diedit & lolos `php -l`.
- `database\setup.php` & `ai-test.php`: dev utilities, sengaja tidak diubah styling.
- `.agents\skills\design-taste-frontend\SKILL.md` & `high-end-visual-design\SKILL.md`: pedoman anti-slop (sudah dibaca).
