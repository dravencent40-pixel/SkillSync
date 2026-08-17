<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $hash = Hash::make('password123');
        $now = now();

        // ── Users ──────────────────────────────────────────────
        DB::table('users')->insert([
            [
                'id' => 1,
                'name' => 'Admin Goodeva',
                'email' => 'admin@goodeva.tech',
                'password_hash' => $hash,
                'role' => 'mitra',
                'avatar_initial' => 'AG',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Rafi Firmansyah',
                'email' => 'rafi@smkn9bekasi.sch.id',
                'password_hash' => $hash,
                'role' => 'siswa',
                'avatar_initial' => 'RF',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Sinta Amelia',
                'email' => 'sinta@smkn9bekasi.sch.id',
                'password_hash' => $hash,
                'role' => 'siswa',
                'avatar_initial' => 'SA',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // ── Student Profiles ──────────────────────────────────
        DB::table('student_profiles')->insert([
            [
                'user_id' => 2,
                'nis' => '20230001',
                'sekolah' => 'SMKN 9 Bekasi',
                'jurusan' => 'Rekayasa Perangkat Lunak',
                'kelas' => 'XII RPL 1',
                'bio' => 'Siswa aktif yang tertarik pada pengembangan web dan keamanan aplikasi.',
                'github_url' => 'https://github.com/rafifirmansyah',
            ],
            [
                'user_id' => 3,
                'nis' => '20230002',
                'sekolah' => 'SMKN 9 Bekasi',
                'jurusan' => 'Rekayasa Perangkat Lunak',
                'kelas' => 'XII RPL 1',
                'bio' => 'Siswa dengan minat di UI/UX design dan mobile development.',
                'github_url' => 'https://github.com/sintamelia',
            ],
        ]);

        // ── Company Profiles ──────────────────────────────────
        DB::table('company_profiles')->insert([
            [
                'user_id' => 1,
                'company_name' => 'Goodeva Technology',
                'industry' => 'Teknologi Informasi',
                'website' => 'https://goodeva.co.id',
                'about' => 'Perusahaan teknologi yang berfokus pada solusi digital untuk pendidikan dan industri.',
            ],
        ]);

        // ── Skill Profiles ────────────────────────────────────
        DB::table('skill_profiles')->insert([
            [
                'user_id' => 2,
                'overall_score' => 72.50,
                'clean_code_avg' => 75.00,
                'security_avg' => 68.00,
                'efficiency_avg' => 74.50,
                'comprehension_avg' => 73.00,
                'tasks_completed' => 3,
                'badge' => 'silver',
                'strengths' => 'Penulisan kode rapi, pemahaman dasar SQL injection.',
                'weaknesses' => 'Perlu peningkatan pada validasi input dan optimasi query.',
                'narrative' => 'Rafi menunjukkan pemahaman yang baik terhadap clean code. Skor keamanan perlu ditingkatkan, terutama pada aspek validasi input.',
                'is_public' => true,
            ],
            [
                'user_id' => 3,
                'overall_score' => 65.00,
                'clean_code_avg' => 70.00,
                'security_avg' => 58.00,
                'efficiency_avg' => 67.00,
                'comprehension_avg' => 65.00,
                'tasks_completed' => 2,
                'badge' => 'bronze',
                'strengths' => 'Konsistensi penamaan variabel, dokumentasi cukup baik.',
                'weaknesses' => 'Keamanan dasar perlu diperkuat, terutama XSS prevention.',
                'narrative' => 'Sinta memiliki fondasi clean code yang baik namun perlu latihan lebih pada aspek keamanan aplikasi web.',
                'is_public' => true,
            ],
        ]);

        // ── Skill Profile Tracks (Web Development) ────────────
        DB::table('skill_profile_tracks')->insert([
            [
                'user_id' => 2,
                'category_id' => 1,
                'overall_score' => 74.00,
                'criterion1_score' => 76.00,
                'criterion2_score' => 68.00,
                'criterion3_score' => 78.00,
                'comprehension_avg' => 75.00,
                'tasks_completed' => 2,
            ],
            [
                'user_id' => 3,
                'category_id' => 1,
                'overall_score' => 66.00,
                'criterion1_score' => 70.00,
                'criterion2_score' => 56.00,
                'criterion3_score' => 72.00,
                'comprehension_avg' => 66.00,
                'tasks_completed' => 1,
            ],
        ]);

        // ── Skill Profile Tracks (Data & Backend) ─────────────
        DB::table('skill_profile_tracks')->insert([
            [
                'user_id' => 2,
                'category_id' => 2,
                'overall_score' => 71.00,
                'criterion1_score' => 74.00,
                'criterion2_score' => 68.00,
                'criterion3_score' => 71.00,
                'comprehension_avg' => 71.00,
                'tasks_completed' => 1,
            ],
        ]);
    }
}
