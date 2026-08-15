<?php

namespace App\Services\Agents;

/**
 * SkillSync — AIClient
 * Lapisan tipis di atas Groq API (OpenAI-compatible chat completions, gratis).
 *
 * Jika GROQ_API_KEY tidak diset, isAvailable() akan mengembalikan false
 * dan setiap Agent (Reviewer, Mentor, dst) otomatis jatuh ke logika heuristik
 * lokal miliknya masing-masing — sehingga project tetap bisa didemokan
 * end-to-end tanpa API key maupun koneksi internet.
 */
class AIClient
{
    private string $apiKey;
    private string $model;
    private string $lastError = '';

    public function __construct()
    {
        $this->apiKey = config('ai.groq_api_key', getenv('GROQ_API_KEY') ?: '');
        $this->model  = config('ai.model', 'llama-3.3-70b-versatile');
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    /** Pesan error teknis terakhir (curl error / HTTP status / potongan respons) — untuk diagnosa. */
    public function lastError(): string
    {
        return $this->lastError;
    }

    /**
     * Kirim satu permintaan ke Groq. Mengembalikan teks jawaban, atau null jika gagal.
     *
     * @param string $system   System prompt (mendefinisikan peran agent)
     * @param array  $messages Riwayat pesan [['role'=>'user'|'assistant','content'=>'...'], ...]
     */
    public function complete(string $system, array $messages, int $maxTokens = 1024): ?string
    {
        $this->lastError = '';

        if (!$this->isAvailable()) {
            $this->lastError = 'GROQ_API_KEY kosong.';
            return null;
        }

        if (!extension_loaded('curl')) {
            $this->lastError = 'Ekstensi PHP curl tidak aktif. Aktifkan extension=curl di php.ini lalu restart Apache.';
            report($this->lastError);
            return null;
        }

        // Groq memakai format OpenAI: system prompt jadi pesan pertama beserta role "system".
        $chatMessages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $messages
        );

        $payload = json_encode([
            'model'      => $this->model,
            'max_tokens' => $maxTokens,
            'messages'   => $chatMessages,
        ]);

        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            // Penyebab paling umum di XAMPP Windows: curl tidak menemukan CA bundle
            // sehingga verifikasi SSL ke api.groq.com gagal (errno 60).
            $hint = ($curlErrno === 60 || stripos($curlError, 'SSL certificate') !== false)
                ? ' → Ini masalah SSL certificate bawaan XAMPP Windows, lihat ai-test.php untuk cara perbaikan.'
                : '';
            $this->lastError = "cURL gagal (errno {$curlErrno}): {$curlError}{$hint}";
            report('AIClient cURL error: ' . $this->lastError);
            return null;
        }

        if ($httpCode !== 200) {
            $this->lastError = "HTTP {$httpCode} dari Groq API: " . substr($response, 0, 500);
            report('AIClient error: ' . $this->lastError);
            return null;
        }

        $data = json_decode($response, true);
        $text = $data['choices'][0]['message']['content'] ?? '';

        if ($text === '') {
            $this->lastError = 'Respons API tidak berisi teks: ' . substr($response, 0, 300);
        }
        return $text !== '' ? $text : null;
    }

    /**
     * Sama seperti complete(), tapi mengharapkan (dan membersihkan) jawaban JSON murni.
     * Mengembalikan array hasil decode, atau null jika gagal/tidak tersedia.
     */
    public function completeJson(string $system, array $messages, int $maxTokens = 1024): ?array
    {
        $text = $this->complete($system . "\n\nPENTING: Balas HANYA dengan objek JSON valid, tanpa teks lain, tanpa markdown code fence.", $messages, $maxTokens);
        if ($text === null) {
            return null;
        }
        $clean = trim(preg_replace('/```json|```/', '', $text));
        $decoded = json_decode($clean, true);
        return is_array($decoded) ? $decoded : null;
    }
}
