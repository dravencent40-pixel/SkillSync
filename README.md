# SkillSync AI

**AI Technical Project Lead & Assessment Agent** untuk siswa SMK dan perusahaan mitra magang.

SkillSync AI mengotomatisasi tiga hal yang biasanya makan waktu guru pembimbing dan tim
rekrutmen: memberi studi kasus yang relevan, mengaudit hasil kerja siswa secara objektif, dan
menyaring talenta lewat skor kompetensi yang transparan — bukan cuma CV.

Sistem ini **rubric-driven dan multi-divisi**: setiap kategori studi kasus punya bentuk
submission serta 3 kriteria penilaiannya sendiri, sehingga tidak hanya kode pemrograman yang
dinilai, tapi juga UI/UX Design, Jaringan & Infrastruktur, dan kategori lain.

Dibangun dengan **PHP native (tanpa framework)** + **MySQL**, terintegrasi dengan **Groq API
(Llama 3.3 70B)** untuk kecerdasan agent, dengan **fallback heuristik lokal** sehingga tetap
berjalan penuh tanpa API key maupun koneksi internet.

> Proyek ini dikembangkan oleh **Kelompok Tekabe** untuk Lomba AI Agent Innovation — Goodeva
> Technology (Kategori Pendidikan/Inovatif).

---

## Fitur

- **Agent Task Issuer** — merekomendasikan studi kasus, diprioritaskan pada kategori dengan
  skor siswa paling lemah, lengkap dengan alasan personalisasi yang ditampilkan ke siswa.
- **Agent Reviewer & Auditor** — audit otomatis berbasis **rubric per kategori**
  (submission `code` / `design` / `network` / `general`). Untuk submission kode, pemeriksaan
  keamanan kritikal (SQL Injection, XSS, RCE, kredensial hardcoded) berjalan **deterministik**
  berdampingan dengan AI, sehingga skor keamanan tidak bisa "dilunakkan" oleh model bahasa —
  tiap temuan ditandai sumbernya (✓ Verified vs AI Judgment).
- **Agent Defense** — lapisan anti-cheat: sesi pembelaan **wajib** setelah audit, mengajukan
  4–5 pertanyaan yang merujuk keputusan spesifik di hasil kerja siswa sendiri, untuk
  membedakan pemahaman asli dari hasil generate AI. Skor pemahaman (`comprehension`) ikut
  membentuk skor keseluruhan.
- **Agent Mentor** — chatbot interaktif yang membimbing lewat *hint* bertahap, bukan jawaban
  jadi, dengan konteks langsung dari hasil audit submission siswa.
- **Agent Profile Generator** — mengagregasi seluruh hasil audit menjadi skor kompetensi
  transparan, termasuk **breakdown per divisi** (per kategori studi kasus), narasi kualitatif,
  kelebihan/kekurangan, dan badge (Pemula → Junior Ready → Job Ready → Top Talent).
- **Activity Timeline** — setiap aksi kelima agent tercatat dan ditampilkan sebagai linimasa
  di dashboard, jadi alur kerja multi-agent terlihat nyata, bukan klaim di atas kertas.
- **Talent Pool untuk Mitra** — jelajahi talenta terurut skor dengan pencarian berdasarkan
  nama/jurusan, lihat profil detail (skor per divisi, narasi, riwayat studi kasus), pratinjau
  CV, dan lacak status rekrutmen (disimpan → dihubungi → interview → magang).
- **Upload CV** — siswa melengkapi profil dengan CV PDF (max 5 MB), dilihat mitra lewat
  pratinjau langsung di halaman talent detail.
- **Indikator Mode AI** — badge transparan di setiap halaman: *Groq AI Aktif* atau
  *Mode Heuristik Lokal*; mode yang dipakai juga dicatat per submission/sesi pembelaan.
- **Keamanan** — proteksi CSRF (`hash_equals`) pada form berisiko: login, register, submit
  solusi, sesi pembelaan, upload CV, toggle visibilitas profil, dan update status rekrutmen.
  Password di-hash bcrypt, semua query memakai PDO prepared statements, upload divalidasi MIME
  dan disimpan dengan nama acak, serta ada cek kepemilikan data (ownership) di tiap halaman.

## Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | PHP 8.1+ (native, tanpa framework) |
| Database | MySQL 8 / MariaDB 10.5+ |
| AI | Groq API — Llama 3.3 70B (opsional, ada fallback heuristik lokal) |
| Frontend | HTML + Tailwind (CDN) + vanilla JS |

## Instalasi

**Kebutuhan:** PHP 8.1+ dengan ekstensi `pdo_mysql`, `mbstring`, dan `curl`; MySQL 8 /
MariaDB 10.5+.

```bash
# 1. Clone repo
git clone https://github.com/dravencent40-pixel/SkillSync-AI.git
cd skillsync

# 2. Import skema database
mysql -u root -p < database/schema.sql
mysql -u root -p skillsync_ai < database/seed.sql   # data demo (6 kategori, 6 studi kasus), opsional
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
urutan tanggalnya: `migrate_narrative.sql`, `migrate_track_scores.sql`, `migrate_defense.sql`,
`migrate_multitrack.sql`, `migrate_accounts_cv.sql`. Instalasi baru cukup `schema.sql`
(sudah mencakup seluruh kolom/tabel migrasi).

## Kredensial Demo

Tersedia setelah import `database/seed.sql` — semua password: `password123`

| Role  | Email |
|-------|-------|
| Mitra | admin@goodeva.tech |
| Siswa | rafi@smkn9bekasi.sch.id |
| Siswa | sinta@smkn9bekasi.sch.id |

## Alur Pengguna

**Siswa:** daftar → dashboard menampilkan skor per divisi & rekomendasi task dari Agent Task
Issuer → kerjakan studi kasus (kode/desain/jaringan sesuai kategori, bisa dengan lampiran file
atau link eksternal) → hasil diaudit Agent Reviewer & Auditor → **selesaikan sesi pembelaan
(Agent Defense)** → diskusi lanjut dengan Agent Mentor bila perlu → skor terakumulasi otomatis
di Profil Skill lengkap dengan breakdown per divisi dan linimasa aktivitas. Siswa juga bisa
mengunggah CV agar terlihat oleh mitra.

**Mitra:** daftar → terbitkan studi kasus dari industri nyata → pantau submission masuk →
jelajahi Talent Pool terurut skor (cari berdasarkan nama/jurusan) → buka profil detail siswa
(skor per divisi, narasi, CV) → tandai status rekrutmen.

## Catatan & Keterbatasan

- `ai-test.php` adalah alat diagnosa koneksi ke Groq API (butuh login) — **hapus atau
  lindungi file ini sebelum deploy ke lingkungan publik.**
- Form "Terbitkan Studi Kasus" di `tasks.php` (mitra) belum dilindungi CSRF.
- `upload_cv.php` adalah form ber-autentikasi tapi belum ada rate-limiting — pertimbangkan
  ini bila dipakai di produksi nyata.
- Mode heuristik lokal (tanpa `GROQ_API_KEY`) berbasis regex untuk kode — cakupan deteksinya
  lebih sempit dibanding audit lewat AI; untuk submission non-kode penilaian hanya berdasar
  kelengkapan (cap skor 70), dan skor sesi pembelaan dibatasi (cap 75). Ini memang dirancang
  sebagai *fallback*, bukan pengganti.

## Kontak

Dibuat oleh **Kelompok Tekabe**

- taufiqridhoo34@gmail.com
- riwantoraihan@gmail.com
