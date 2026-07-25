# SkillSync AI

AI Technical Project Lead & Assessment Agent untuk siswa SMK dan perusahaan mitra magang.
PHP native (tanpa framework) + MySQL, dengan 4 agent: **Task Issuer**, **Reviewer & Auditor**,
**Mentor**, dan **Profile Generator**.

## Menjalankan

1. Butuh PHP 8.1+ dengan ekstensi **pdo_mysql** dan **mbstring** (keduanya wajib —
   `MentorAgent` dan helper string di `functions.php` memakai fungsi `mb_*`).
2. Buat database lalu import skema:
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p skillsync_ai < database/seed.sql   # data demo (opsional)
   ```
   Atau pakai installer web: buka `database/setup.php` di browser.
3. Salin `config/config.example.php` menjadi `config/config.php` (`cp config/config.example.php config/config.php`),
   lalu isi sesuai environment kamu (host DB, `APP_URL`, dan `GROQ_API_KEY` bila mau mode
   Groq AI aktif — tanpa key, aplikasi tetap jalan penuh di **mode heuristik lokal**).
   `config/config.php` sengaja ada di `.gitignore` — jangan pernah commit file ini karena
   berisi API key & kredensial DB.
4. Arahkan document root ke folder ini, atau jalankan dev server:
   ```bash
   php -S localhost:8000
   ```
5. **Jika kamu upgrade dari instalasi lama** (sebelum update ini), jalankan migrasi kolom baru:
   ```bash
   mysql -u root -p skillsync_ai < database/migrate_narrative.sql
   ```

## Yang Diperbarui di Update Ini

Fokus utama update ini: memperkuat lapisan **AI Agent** (bukan cuma tampilan), karena
itu yang paling dinilai di kompetisi — plus menutup celah keamanan dasar untuk kesiapan produksi.

### 1. Bug nyata di Agent Reviewer & Auditor — sudah diperbaiki
Regex deteksi SQL Injection versi sebelumnya hanya mengecek `$_GET`/`$_POST` yang
*langsung* digabung ke query. Pola paling umum di kode siswa nyata — variabel biasa
(`$username = $_POST[...]`) yang baru diinterpolasi ke string SQL belakangan — **lolos
tanpa terdeteksi**, termasuk lolos pada soal SQLi bawaan `seed.sql` sendiri. Sudah
diverifikasi lewat pengujian end-to-end: sebelum perbaikan skor keamanan kode rentan
tersebut 100/100, sekarang 60/100 dengan temuan "Potensi SQL Injection" tercatat.

### 2. Hybrid Audit Trail (transparansi, bukan black-box)
Sebelumnya: saat Groq API aktif, semua temuan murni opini AI (probabilistik).
Sekarang: pemeriksaan keamanan deterministik (SQLi, XSS, secret hardcoded, fungsi
berbahaya) **selalu dijalankan berdampingan** dengan AI, dan skor keamanan diberi
*hard cap* — AI tidak bisa "melunakkan" skor padahal ada bukti pasti pelanggaran.
Tiap temuan diberi label sumber yang tampil di UI:
- **✓ Verified** (hijau) — ditemukan lewat pemeriksaan pola deterministik
- **AI Judgment** — penilaian kualitatif dari Groq (Llama 3.3)

### 3. Agent Task Issuer sekarang transparan soal alasan rekomendasi
`recommendedTasks()` diganti `recommend()` yang mengembalikan alasan personalisasi,
mis. *"Direkomendasikan karena skor Keamanan kamu masih 65/100"* — ditampilkan
langsung di dashboard siswa, bukan cuma daftar tanpa konteks.

### 4. Agent Profile Generator sekarang menulis narasi untuk mitra
Skor tetap dihitung murni dari data `ai_reviews` (bukan dikarang LLM, supaya
dipertanggungjawabkan) — tapi sekarang Agent Profile Generator juga menulis satu
paragraf ringkasan kualitatif (via Groq, fallback ke template deterministik jika
AI tidak tersedia) yang tampil di halaman detail talent untuk mitra. Kolom baru:
`skill_profiles.narrative`.

### 5. Agent Activity Timeline (fitur baru)
Tabel `activity_logs` di skema sebelumnya sudah dirancang tapi **tidak pernah dipakai**
di kode manapun. Sekarang setiap aksi ke-4 agent dicatat (rekomendasi tugas, audit
selesai, balasan mentor, profil diperbarui) dan ditampilkan sebagai timeline di
dashboard siswa maupun mitra — supaya sistem multi-agent ini terlihat benar-benar
bekerja, bukan cuma klaim di proposal.

### 6. Indikator Mode AI
Badge kecil di header ("● Groq AI Aktif" / "● Mode Heuristik Lokal") — transparansi
jujur soal mode yang sedang aktif, konsisten di semua halaman.

### 7. Keamanan: CSRF protection
Sebelumnya tidak ada proteksi CSRF sama sekali. Ditambahkan token CSRF (session-based,
`hash_equals` untuk mencegah timing attack) pada form login, submit kode, dan update
status rekrutmen di portal mitra. Sudah diuji: token palsu ditolak dan tidak
menghasilkan sesi.

### 8. Alat Diagnosa Koneksi AI (`ai-test.php`)
Sebelumnya, kalau panggilan ke Groq API gagal (SSL, API key salah, dsb), sistem
diam-diam jatuh ke mode heuristik tanpa penjelasan — sangat menyulitkan debugging,
apalagi masalah SSL certificate di XAMPP Windows yang cukup umum. Sekarang tersedia
`ai-test.php` (perlu login) yang menjalankan satu panggilan uji nyata ke Groq dan
menampilkan detail teknis persis: HTTP status, pesan error dari Groq, atau
kalau masalahnya SSL certificate bawaan XAMPP — dikasih instruksi perbaikan langkah
demi langkah. **Hapus atau lindungi file ini sebelum deploy ke publik.**

## Kredensial Demo (dari seed.sql)

| Role  | Email                          | Password    |
|-------|----------------------------------|-------------|
| Mitra | admin@goodeva.tech               | password123 |
| Siswa | rafi@smkn9bekasi.sch.id          | password123 |
| Siswa | sinta@smkn9bekasi.sch.id         | password123 |

## Struktur Agent

| Agent                  | File                                              |
|-------------------------|---------------------------------------------------|
| Task Issuer              | `includes/agents/TaskIssuerAgent.php`             |
| Reviewer & Auditor       | `includes/agents/ReviewerAuditorAgent.php`        |
| Mentor                   | `includes/agents/MentorAgent.php`                 |
| Profile Generator        | `includes/agents/ProfileGeneratorAgent.php`       |
| Klien AI (Groq API)    | `includes/agents/AIClient.php`                    |

## Keterbatasan yang Masih Perlu Diperhatikan

- CSRF belum dipasang di seluruh form (baru: login, submit kode, status rekrutmen —
  yang paling berisiko). Form lain seperti buat task baru oleh mitra belum dilindungi.
- `upload_cv.php` adalah form publik tanpa autentikasi — pertimbangkan rate-limiting
  bila dipakai di produksi nyata.
- Mode heuristik lokal (tanpa API key) tetap berbasis regex — cakupan deteksinya jauh
  lebih sempit dibanding audit oleh Groq, ini memang dirancang sebagai *fallback*,
  bukan pengganti penuh.
