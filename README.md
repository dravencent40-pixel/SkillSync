# SkillSync

**AI Technical Project Lead & Assessment Agent** untuk siswa SMK dan perusahaan mitra magang.

SkillSync mengotomatisasi tiga hal yang biasanya makan waktu guru pembimbing dan tim
rekrutmen: memberi studi kasus yang relevan, mengaudit kode siswa secara objektif, dan
menyaring talenta lewat skor kompetensi yang transparan — bukan cuma CV.

Dibangun dengan **PHP native (tanpa framework)** + **MySQL**, terintegrasi dengan **Groq API
(Llama 3.3 70B)** untuk kecerdasan agent, dengan **fallback heuristik lokal** sehingga tetap
berjalan penuh tanpa API key maupun koneksi internet.

> Proyek ini dikembangkan oleh **Kelompok Tekabe** untuk Lomba AI Agent Innovation — Goodeva
> Technology (Kategori Pendidikan/Inovatif).

---

## Fitur

- **Agent Task Issuer** — merekomendasikan studi kasus, diprioritaskan pada kategori dengan
  skor siswa paling lemah, lengkap dengan alasan personalisasi yang ditampilkan ke siswa.
- **Agent Reviewer & Auditor** — audit kode otomatis (clean code, keamanan, efisiensi).
  Pemeriksaan keamanan kritikal (SQL Injection, XSS, kredensial hardcoded) berjalan
  **deterministik** berdampingan dengan AI, sehingga skor keamanan tidak bisa "dilunakkan"
  oleh model bahasa — tiap temuan ditandai sumbernya (✓ Verified vs AI Judgment).
- **Agent Mentor** — chatbot interaktif yang membimbing lewat *hint* bertahap, bukan jawaban
  jadi, dengan konteks langsung dari hasil audit submission siswa.
- **Agent Defense** — lapisan anti-cheat: mengajukan pertanyaan susulan yang merujuk
  keputusan spesifik di kode siswa sendiri, untuk membedakan pemahaman asli dari kode yang
  sekadar ditempel dari hasil generate AI.
- **Agent Profile Generator** — mengagregasi seluruh hasil audit menjadi skor kompetensi
  transparan (Clean Code, Keamanan, Efisiensi) plus narasi kualitatif untuk mitra industri.
- **Activity Timeline** — setiap aksi keempat/lima agent tercatat dan ditampilkan sebagai
  linimasa di dashboard, jadi alur kerja multi-agent terlihat nyata, bukan klaim di atas kertas.
- **Talent Pool untuk Mitra** — jelajahi & filter talenta berdasarkan skor, lihat profil
  detail, dan lacak status rekrutmen (disimpan → dihubungi → interview → magang).
- **Upload CV** — siswa dapat melengkapi profil dengan CV, dilihat mitra lewat pratinjau modal.
- **Indikator Mode AI** — badge transparan di setiap halaman: *Groq AI Aktif* atau
  *Mode Heuristik Lokal*.
- **Keamanan dasar** — proteksi CSRF (session-based, `hash_equals`) pada form-form berisiko
  tinggi: login, submit kode, dan update status rekrutmen.

## Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | PHP 8.1+ (native, tanpa framework) |
| Database | MySQL 8 / MariaDB 10.5+ |
| AI | Groq API — Llama 3.3 70B (opsional, ada fallback heuristik lokal) |
| Frontend | HTML + Tailwind (CDN) + vanilla JS |

## Instalasi

**Kebutuhan:** PHP 8.1+ dengan ekstensi `pdo_mysql` dan `mbstring`, MySQL 8 / MariaDB 10.5+.

```bash
# 1. Clone repo
git clone https://github.com/dravencent40-pixel/SkillSync.git
cd skillsync

# 2. Import skema database
mysql -u root -p < database/schema.sql
mysql -u root -p skillsync < database/seed.sql   # data demo, opsional
# atau pakai installer web: buka database/setup.php di browser

# 3. Salin & isi konfigurasi
cp config/config.example.php config/config.php
# edit config/config.php: DB_HOST, DB_NAME, DB_USER, DB_PASS, APP_URL,
# dan GROQ_API_KEY bila ingin mode AI penuh aktif (gratis di https://console.groq.com/keys)

# 4. Jalankan
php -S localhost:8000
# buka http://localhost:8000
```

> `config/config.php` sudah masuk `.gitignore` — jangan pernah commit file ini karena berisi
> API key & kredensial database. Selalu commit `config/config.example.php` sebagai template.

**Upgrade dari instalasi lama?** Jalankan file migrasi yang relevan di `database/` sesuai
urutan tanggalnya (`migrate_narrative.sql`, `migrate_track_scores.sql`, `migrate_defense.sql`,
`migrate_multitrack.sql`, `migrate_accounts_cv.sql`).

## Kredensial Demo

Tersedia setelah import `database/seed.sql` — semua password: `password123`

| Role  | Email |
|-------|-------|
| Mitra | admin@goodeva.tech |
| Siswa | rafi@smkn9bekasi.sch.id |
| Siswa | sinta@smkn9bekasi.sch.id |

## Alur Pengguna

**Siswa:** daftar → dashboard menampilkan skor & rekomendasi task dari Agent Task Issuer →
kerjakan studi kasus → kode diaudit Agent Reviewer & Auditor → jawab pertanyaan susulan dari
Agent Defense → diskusi lanjut dengan Agent Mentor bila perlu → skor terakumulasi otomatis
di Profil Skill, lengkap dengan linimasa aktivitas.

**Mitra:** daftar → terbitkan studi kasus dari industri nyata → pantau submission masuk →
jelajahi Talent Pool terurut skor → buka profil detail siswa (termasuk CV) → tandai status
rekrutmen.

## Catatan & Keterbatasan

- `ai-test.php` adalah alat diagnosa koneksi ke Groq API (butuh login) — **hapus atau
  lindungi file ini sebelum deploy ke lingkungan publik.**
- Proteksi CSRF baru dipasang pada form berisiko tinggi (login, submit kode, status
  rekrutmen); form lain seperti pembuatan task baru oleh mitra belum dilindungi.
- `upload_cv.php` adalah form ber-autentikasi tapi belum ada rate-limiting — pertimbangkan
  ini bila dipakai di produksi nyata.
- Mode heuristik lokal (tanpa `GROQ_API_KEY`) berbasis regex — cakupan deteksinya lebih
  sempit dibanding audit lewat AI. Ini memang dirancang sebagai *fallback*, bukan pengganti.

## Kontak

Dibuat oleh **Kelompok Tekabe**

- taufiqridhoo34@gmail.com
- riwantoraihan@gmail.com
