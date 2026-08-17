<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TaskCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'submission_type' => 'code',
                'rubric_criteria' => json_encode([
                    ['key' => 'clean_code', 'label' => 'Clean Code', 'description' => 'Penamaan, struktur, komentar, konsistensi'],
                    ['key' => 'security', 'label' => 'Keamanan', 'description' => 'SQL Injection, XSS, validasi input, secret hardcoded'],
                    ['key' => 'efficiency', 'label' => 'Efisiensi', 'description' => 'Kompleksitas, query N+1, redundansi'],
                ]),
            ],
            [
                'name' => 'Data & Backend',
                'slug' => 'data-backend',
                'submission_type' => 'code',
                'rubric_criteria' => json_encode([
                    ['key' => 'clean_code', 'label' => 'Clean Code', 'description' => 'Penamaan, struktur, komentar, konsistensi'],
                    ['key' => 'security', 'label' => 'Keamanan', 'description' => 'SQL Injection, XSS, validasi input, secret hardcoded'],
                    ['key' => 'efficiency', 'label' => 'Efisiensi', 'description' => 'Kompleksitas, query N+1, redundansi'],
                ]),
            ],
            [
                'name' => 'Keamanan Aplikasi',
                'slug' => 'keamanan-aplikasi',
                'submission_type' => 'code',
                'rubric_criteria' => json_encode([
                    ['key' => 'clean_code', 'label' => 'Clean Code', 'description' => 'Penamaan, struktur, komentar, konsistensi'],
                    ['key' => 'security', 'label' => 'Keamanan', 'description' => 'SQL Injection, XSS, validasi input, secret hardcoded'],
                    ['key' => 'efficiency', 'label' => 'Efisiensi', 'description' => 'Kompleksitas, query N+1, redundansi'],
                ]),
            ],
            [
                'name' => 'Mobile & UI',
                'slug' => 'mobile-ui',
                'submission_type' => 'code',
                'rubric_criteria' => json_encode([
                    ['key' => 'clean_code', 'label' => 'Clean Code', 'description' => 'Penamaan, struktur, komentar, konsistensi'],
                    ['key' => 'security', 'label' => 'Keamanan', 'description' => 'SQL Injection, XSS, validasi input, secret hardcoded'],
                    ['key' => 'efficiency', 'label' => 'Efisiensi', 'description' => 'Kompleksitas, query N+1, redundansi'],
                ]),
            ],
            [
                'name' => 'UI/UX Design',
                'slug' => 'ui-ux-design',
                'submission_type' => 'design',
                'rubric_criteria' => json_encode([
                    ['key' => 'visual_hierarchy', 'label' => 'Hierarki & Layout Visual', 'description' => 'Alur mata, whitespace, tipografi, grid'],
                    ['key' => 'design_consistency', 'label' => 'Konsistensi Design System', 'description' => 'Komponen reusable, spacing/warna konsisten'],
                    ['key' => 'usability', 'label' => 'Usability & Aksesibilitas', 'description' => 'Kemudahan pengguna, kontras warna, ukuran tap target'],
                ]),
            ],
            [
                'name' => 'Jaringan & Infrastruktur',
                'slug' => 'jaringan-infrastruktur',
                'submission_type' => 'network',
                'rubric_criteria' => json_encode([
                    ['key' => 'topology_design', 'label' => 'Desain Topologi & Perencanaan', 'description' => 'Pemilihan perangkat, alokasi IP/VLAN, redundansi'],
                    ['key' => 'configuration', 'label' => 'Konfigurasi & Implementasi', 'description' => 'Ketepatan konfigurasi perangkat, keamanan akses'],
                    ['key' => 'documentation', 'label' => 'Dokumentasi & Troubleshooting', 'description' => 'Kejelasan dokumentasi, rencana penanganan gangguan'],
                ]),
            ],
        ];

        DB::table('task_categories')->insert($categories);
    }
}
