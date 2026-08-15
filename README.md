# SkillSync

**AI Technical Project Lead & Assessment Agent** untuk siswa SMK dan perusahaan mitra magang.

SkillSync mengotomatisasi tiga hal yang biasanya makan waktu guru pembimbing dan tim
rekrutmen: memberi studi kasus yang relevan, mengaudit kode siswa secara objektif, dan
menyaring talenta lewat skor kompetensi yang transparan — bukan cuma CV.

Dibangun dengan **Laravel 12 + Inertia + React 19** + **MySQL**, terintegrasi dengan **Groq API
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
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Inertia.js + React 19 + Tailwind CSS (Vite) |
| Database | MySQL 8 / MariaDB 10.5+ |
| AI | Groq API — Llama 3.3 70B (opsional, ada fallback heuristik lokal) |

## Instalasi

**Kebutuhan:** PHP 8.2+ (ekstensi `pdo_mysql`, `mbstring`, `curl`), Composer, Node.js 20+,
MySQL 8 / MariaDB 10.5+.

```bash
# 1. Clone repo
git clone https://github.com/dravencent40-pixel/SkillSync-AI.git
cd skillsync

# 2. Konfigurasi
composer install
copy .env.example .env
# edit .env: DB_DATABASE, DB_USERNAME, DB_PASSWORD, dan GROQ_API_KEY bila ingin
# mode AI penuh aktif (gratis di https://console.groq.com/keys)

# 3. Bangun aset frontend
npm install
npm run build        # produksi — atau `npm run dev` saat pengembangan

# 4. Jalankan
php artisan key:generate
php artisan serve
# buka http://localhost:8000
```

> `.env` berisi API key & kredensial database dan sudah masuk `.gitignore` — jangan pernah
> commit. Selalu commit `.env.example` sebagai template.

**Struktur penting:**
- `app/Http/Controllers/` — controller Inertia (render React page)
- `app/Services/Agents/` — enam agent AI (AIClient, TaskIssuer, Reviewer, Mentor, Defense, ProfileGenerator)
- `resources/js/Pages/` — halaman React per route
- `routes/web.php` — seluruh route aplikasi

## Alur Pengguna

**Siswa:** daftar → dashboard menampilkan skor & rekomendasi task dari Agent Task Issuer →
kerjakan studi kasus → kode diaudit Agent Reviewer & Auditor → jawab pertanyaan susulan dari
Agent Defense → diskusi lanjut dengan Agent Mentor bila perlu → skor terakumulasi otomatis
di Profil Skill, lengkap dengan linimasa aktivitas.

**Mitra:** daftar → terbitkan studi kasus dari industri nyata → pantau submission masuk →
jelajahi Talent Pool terurut skor → buka profil detail siswa (termasuk CV) → tandai status
rekrutmen.

## Catatan & Keterbatasan

- Skema database tidak disertakan di repository ini (diimpor langsung dari sistem lama);
  untuk menjalankan aplikasi, buat database MySQL (default: `skillsync`) dan isi konfigurasi
  di `.env`. Sebaiknya ditambahkan Laravel migration + seeder ke depan.
- Kolom password memakai `password_hash` (skema legacy); Laravel Auth sudah dipetakan lewat
  `getAuthPassword()`/`getAuthPasswordName()` di `App\Models\User`.
- Mode heuristik lokal (tanpa `GROQ_API_KEY`) berbasis regex — cakupan deteksinya lebih
  sempit dibanding audit lewat AI. Ini memang dirancang sebagai *fallback*, bukan pengganti.
- Halaman mitra (Talent Pool) & panel siswa memakai route yang diproteksi middleware `role:`.
- Jika `php artisan serve` dipakai untuk CV: file PDF disajikan dari `public/uploads/cvs`.

## Kontak

Dibuat oleh **Kelompok Tekabe**

- taufiqridhoo34@gmail.com
- riwantoraihan@gmail.com
