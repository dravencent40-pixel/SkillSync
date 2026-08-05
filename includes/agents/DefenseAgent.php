<?php
require_once __DIR__ . '/AIClient.php';

/**
 * SkillSync — Agent Defense
 *
 * Anti-cheat layer di atas Agent Reviewer & Auditor. Skor kode saja tidak bisa
 * membedakan "siswa yang paham keputusan desainnya sendiri" dari "siswa yang
 * cuma menempel hasil generate AI tanpa mengerti isinya" — dua submission bisa
 * identik secara kualitas kode tapi punya pemahaman yang jauh berbeda.
 *
 * Setelah submission direview, Agent Defense mengeluarkan 3-5 pertanyaan
 * singkat yang merujuk KEPUTUSAN SPESIFIK di kode/temuan siswa itu sendiri
 * (bukan pertanyaan generik yang bisa dijawab tanpa membaca kodenya), lalu
 * menilai apakah jawabannya menunjukkan pemahaman nyata. Hasilnya jadi
 * `comprehension_score` yang ikut membentuk skor akhir di Profile Generator —
 * supaya submission yang "kode bagus tapi tidak bisa dijelaskan pemiliknya
 * sendiri" tidak otomatis dapat skor kompetensi tinggi.
 */
class DefenseAgent
{
    private AIClient $ai;

    public function __construct()
    {
        $this->ai = new AIClient();
    }

    /**
     * @param  array $findings Temuan dari ai_reviews (findings_json yang sudah di-decode)
     * @return array{questions:string[], ai_assisted:bool}
     */
    public function generateQuestions(string $code, string $taskBrief, array $findings): array
    {
        if ($this->ai->isAvailable()) {
            $result = $this->generateWithAI($code, $taskBrief, $findings);
            if ($result !== null) {
                return ['questions' => $result, 'ai_assisted' => true];
            }
        }
        return ['questions' => $this->generateLocal($taskBrief, $findings), 'ai_assisted' => false];
    }

    private function generateWithAI(string $code, string $taskBrief, array $findings): ?array
    {
        $findingTitles = implode('; ', array_slice(array_column($findings, 'title'), 0, 5));

        $system = "Kamu adalah SkillSync Defense Examiner. Tugasmu memverifikasi bahwa siswa BENAR-BENAR memahami "
                . "project yang dia submit sendiri — bukan sekadar menempel hasil generate AI tanpa paham isinya. "
                . "Buat 4 pertanyaan singkat berbahasa Indonesia yang HANYA bisa dijawab dengan mudah oleh orang yang "
                . "benar-benar mengerjakan/memahami hasil kerja ini (kode, desain, atau dokumentasi teknis). Rujuk bagian SPESIFIK dari "
                . "hasil kerja atau temuan reviewer (nama variabel/fungsi/komponen/perangkat, keputusan desain, "
                . "potongan logika/konfigurasi tertentu) — JANGAN pertanyaan generik "
                . "seperti 'apa itu CRUD' yang bisa dijawab tanpa lihat hasil kerjanya sama sekali. Variasikan jenis pertanyaan: "
                . "(1) alasan sebuah keputusan desain, (2) apa yang terjadi pada satu edge case tertentu, "
                . "(3) trade-off pendekatan yang dipakai, (4) bagaimana memperluas satu bagian fitur. "
                . "Balas dalam format JSON: {\"questions\":[\"...\",\"...\",\"...\",\"...\"]}";

        $user = "Studi kasus:\n{$taskBrief}\n\nTemuan reviewer atas hasil kerja ini: {$findingTitles}\n\nHasil kerja/kode kiriman siswa:\n```\n{$code}\n```";

        $result = $this->ai->completeJson($system, [['role' => 'user', 'content' => $user]], 700);
        if ($result === null || empty($result['questions']) || !is_array($result['questions'])) {
            return null;
        }
        return array_values(array_slice($result['questions'], 0, 5));
    }

    /**
     * Fallback lokal — pertanyaan template yang tetap merujuk ke temuan
     * spesifik submission (bukan generik total), supaya sesi defense tetap
     * bermakna walau tanpa AI tersambung.
     */
    private function generateLocal(string $taskBrief, array $findings): array
    {
        $questions = [
            'Jelaskan dengan kata-katamu sendiri, apa masalah utama yang diselesaikan studi kasus ini dan bagaimana alur programmu menyelesaikannya dari awal sampai akhir?',
        ];

        foreach (array_slice($findings, 0, 3) as $f) {
            $questions[] = "Reviewer menandai: \"{$f['title']}\". Coba jelaskan kenapa bagian itu bisa terjadi di hasil kerjamu, dan bagaimana cara memperbaikinya?";
        }

        $questions[] = 'Kalau ada satu input yang tidak terduga (misalnya kosong, sangat panjang, atau format aneh) dikirim ke fitur utamamu, apa yang akan terjadi pada program ini? Sudah kamu tangani atau belum?';
        $questions[] = 'Bagian mana dari hasil kerja ini yang paling sulit kamu kerjakan, dan pendekatan apa yang akhirnya kamu pakai?';

        return array_slice($questions, 0, 5);
    }

    /**
     * @param array $qa [['question'=>string,'answer'=>string], ...]
     * @return array{comprehension_score:int, feedback:string, per_question:array, ai_assisted:bool}
     */
    public function evaluateAnswers(string $code, array $qa): array
    {
        if ($this->ai->isAvailable()) {
            $result = $this->evaluateWithAI($code, $qa);
            if ($result !== null) {
                $result['ai_assisted'] = true;
                return $result;
            }
        }
        $result = $this->evaluateLocal($qa);
        $result['ai_assisted'] = false;
        return $result;
    }

    private function evaluateWithAI(string $code, array $qa): ?array
    {
        $system = "Kamu adalah SkillSync Defense Examiner yang menilai SESI PEMBELAAN PROJECT siswa SMK. "
                . "Untuk tiap pasangan pertanyaan-jawaban, nilai apakah jawaban itu menunjukkan PEMAHAMAN NYATA atas "
                . "hasil kerja yang disubmit siswa (bukan cuma jawaban umum/template/asal yang bisa ditulis siapa saja tanpa "
                . "melihat hasil kerjanya). Jawaban yang spesifik, konsisten dengan hasil kerja, dan menunjukkan penalaran diberi skor "
                . "tinggi; jawaban vague, generik, tidak nyambung dengan hasil kerja, atau kosong diberi skor rendah. "
                . "Bersikap adil tapi kritis — tujuannya memverifikasi keaslian pemahaman, bukan menghukum siswa yang "
                . "gaya bahasanya sederhana selama substansinya benar. "
                . "Balas dalam format JSON: {\"per_question\":[{\"score\":0-100,\"feedback\":\"1 kalimat singkat\"}],"
                . "\"comprehension_score\":0-100,\"feedback\":\"ringkasan 2-3 kalimat, membangun, berbahasa Indonesia\"}";

        $qaText = '';
        foreach ($qa as $i => $item) {
            $qaText .= ($i + 1) . ". Q: {$item['question']}\n   A: " . (trim($item['answer']) !== '' ? $item['answer'] : '(tidak dijawab)') . "\n\n";
        }
        $user = "Hasil kerja/kode kiriman siswa:\n```\n{$code}\n```\n\nSesi tanya-jawab:\n{$qaText}";

        $result = $this->ai->completeJson($system, [['role' => 'user', 'content' => $user]], 1200);
        if ($result === null || !isset($result['comprehension_score']) || !isset($result['per_question'])) {
            return null;
        }

        return [
            'comprehension_score' => max(0, min(100, (int) $result['comprehension_score'])),
            'feedback'            => $result['feedback'] ?? 'Sesi pembelaan telah dinilai.',
            'per_question'        => $result['per_question'],
        ];
    }

    /**
     * Fallback heuristik — SANGAT sederhana (panjang jawaban & kata kunci
     * dari pertanyaan itu sendiri) dan sengaja diberi skor lebih konservatif
     * (dipagari maksimum 75) karena tidak benar-benar memverifikasi
     * substansi. Ini demi transparansi: jangan sampai mode fallback
     * memberi skor pemahaman setinggi mode AI padahal jauh lebih lemah.
     */
    private function evaluateLocal(array $qa): array
    {
        $perQuestion = [];
        $total = 0;

        foreach ($qa as $item) {
            $answer = trim($item['answer'] ?? '');
            $wordCount = $answer === '' ? 0 : str_word_count($answer);

            if ($wordCount === 0) {
                $score = 0;
                $fb = 'Tidak dijawab.';
            } elseif ($wordCount < 8) {
                $score = 30;
                $fb = 'Jawaban terlalu singkat untuk menunjukkan pemahaman mendalam.';
            } elseif ($wordCount < 20) {
                $score = 55;
                $fb = 'Jawaban cukup, tapi bisa lebih spesifik merujuk ke bagian kodenya.';
            } else {
                $score = 70;
                $fb = 'Jawaban cukup panjang — perlu verifikasi manual/AI untuk memastikan relevansinya dengan kode.';
            }

            $perQuestion[] = ['score' => $score, 'feedback' => $fb];
            $total += $score;
        }

        $avg = $qa ? (int) round($total / count($qa)) : 0;

        return [
            'comprehension_score' => min(75, $avg), // dipagari — mode heuristik tidak benar-benar verifikasi substansi
            'feedback' => 'Dinilai dengan mode heuristik lokal (belum tersambung ke AI) — hanya berdasarkan panjang '
                        . 'jawaban, BUKAN pemeriksaan substansi. Sambungkan GROQ_API_KEY untuk penilaian pemahaman yang sesungguhnya.',
            'per_question' => $perQuestion,
        ];
    }
}
