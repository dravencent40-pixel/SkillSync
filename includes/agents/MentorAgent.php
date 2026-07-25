<?php
/**
 * SkillSync AI — Agent Mentor
 *
 * Chatbot interaktif yang berperan sebagai mentor teknis: menjawab pertanyaan
 * siswa seputar bug/kodenya, memberi hint (bukan langsung jawaban jadi), dan
 * memberi semangat. Menggunakan Claude jika tersedia, atau logika
 * pattern-matching lokal sebagai fallback.
 */
class MentorAgent
{
    private AIClient $ai;

    public function __construct()
    {
        $this->ai = new AIClient();
    }

    /**
     * @param array  $history      Riwayat percakapan [['sender'=>'user'|'agent','message'=>'...'], ...]
     * @param string $userMessage  Pesan baru dari siswa
     * @param ?array $reviewContext Hasil AI review terkait (opsional, untuk konteks)
     */
    public function reply(array $history, string $userMessage, ?array $reviewContext = null): string
    {
        if ($this->ai->isAvailable()) {
            $result = $this->replyWithAI($history, $userMessage, $reviewContext);
            if ($result !== null) {
                return $result;
            }
        }
        return $this->replyLocal($userMessage, $reviewContext);
    }

    private function replyWithAI(array $history, string $userMessage, ?array $reviewContext): ?string
    {
        $system = "Kamu adalah SkillSync AI Mentor — Senior Tech Lead yang membimbing siswa SMK secara langsung dan hangat. "
                . "Gaya bicara santai tapi profesional, berbahasa Indonesia. JANGAN langsung memberi kode jadi/jawaban penuh; "
                . "beri petunjuk bertahap (hint) agar siswa tetap belajar berpikir, kecuali siswa memang memintanya secara eksplisit. "
                . "Jika ada konteks hasil audit kode, kaitkan jawabanmu dengan temuan tersebut.";

        if ($reviewContext) {
            $system .= "\n\nKonteks hasil audit kode siswa: " . json_encode($reviewContext, JSON_UNESCAPED_UNICODE);
        }

        $messages = [];
        foreach ($history as $h) {
            $messages[] = ['role' => $h['sender'] === 'agent' ? 'assistant' : 'user', 'content' => $h['message']];
        }
        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $this->ai->complete($system, $messages, 600);
    }

    /**
     * Fallback lokal — pattern matching sederhana atas kata kunci umum.
     */
    private function replyLocal(string $message, ?array $reviewContext): string
    {
        $m = mb_strtolower($message);

        if (str_contains($m, 'sql injection') || str_contains($m, 'sqli')) {
            return "Untuk mencegah SQL Injection, ganti query yang menggabungkan string langsung dengan **prepared statement**. "
                 . "Contoh pola: `\$stmt = \$pdo->prepare(\"SELECT * FROM users WHERE username = ?\"); \$stmt->execute([\$username]);`. "
                 . "Coba terapkan pada bagian query yang ditandai di hasil audit, lalu submit ulang ya!";
        }
        if (str_contains($m, 'xss')) {
            return "Untuk XSS, bungkus setiap output yang berasal dari input pengguna dengan `htmlspecialchars(\$data, ENT_QUOTES, 'UTF-8')` "
                 . "sebelum di-echo ke HTML. Sudah dicoba di bagian mana?";
        }
        if (str_contains($m, 'n+1') || str_contains($m, 'lambat') || str_contains($m, 'lemot') || str_contains($m, 'query')) {
            return "Kalau kode kamu memanggil query di dalam loop, itu tanda klasik masalah N+1. Coba ambil semua data yang dibutuhkan "
                 . "dalam SATU query di luar loop (misalnya pakai `WHERE id IN (...)` atau JOIN), baru diproses di memori. "
                 . "Mau aku bantu tunjukkan bagian mana yang perlu diubah dulu?";
        }
        if (str_contains($m, 'error') || str_contains($m, 'bug') || str_contains($m, 'gagal')) {
            return "Boleh share pesan error lengkapnya atau baris berapa yang bermasalah? Coba juga cek dulu: apakah semua variabel "
                 . "sudah didefinisikan sebelum dipakai, dan apakah tanda kurung/kurawal sudah seimbang.";
        }
        if (str_contains($m, 'nilai') || str_contains($m, 'skor')) {
            $ctx = '';
            if ($reviewContext) {
                $ctx = " Berdasarkan audit terakhir, skor kamu: Clean Code {$reviewContext['clean_code_score']}, "
                     . "Keamanan {$reviewContext['security_score']}, Efisiensi {$reviewContext['efficiency_score']}.";
            }
            return "Skor dihitung dari tiga aspek: clean code, keamanan, dan efisiensi kode." . $ctx
                 . " Fokus perbaiki dulu aspek dengan skor paling rendah, dampaknya akan paling terasa.";
        }
        if (str_contains($m, 'makasih') || str_contains($m, 'terima kasih') || str_contains($m, 'thanks')) {
            return "Sama-sama! Semangat terus ya, kirim lagi kalau ada yang mau didiskusikan. 🚀";
        }
        if (str_contains($m, 'halo') || str_contains($m, 'hai') || $m === '') {
            return "Halo! Aku Agent Mentor SkillSync AI. Ceritakan bagian kode atau studi kasus yang bikin kamu stuck, "
                 . "nanti kita bahas bareng step by step.";
        }

        return "Menarik! Coba ceritakan lebih detail — bagian kode mana yang kamu maksud, dan apa yang sudah kamu coba sejauh ini? "
             . "Dengan begitu aku bisa kasih arahan yang lebih tepat sasaran.";
    }
}
