<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class WaBootMekariController extends Controller
{
    use ApiResponse;

    private const NO_ANSWER_TEXT = 'Mohon maaf, untuk pertanyaan ini kami belum memiliki jawabannya. Tim CS kami akan segera membantu Anda 🙏';

    // ======================= LOGIC PENCARIAN DATA =======================

    private function isJamaahNameQuery(string $message): bool
    {
        $text = Str::lower($message);
        $keywords = [
            'apakah ada nama jamaah',
            'list jamaah pada paket',
            'daftar jamaah pada paket',
            'nama saya',
            'atas nama',
            'a.n',
            'jamaah bernama',
            'sudah terdaftar',
            'sudah daftar',
            'status pendaftaran',
            'status jamaah',
            'cek nama',
            'cek jamaah',
            'apakah nama',
            'terdaftar atas nama',
        ];
        return Str::contains($text, $keywords);
    }

    private function findJamaahByNameFuzzy(string $message): array
    {
        $jamaah = $this->loadJamaahContext();
        if (empty($jamaah))
            return [];

        $needle = Str::lower($message);
        $stripPhrases = [
            'apakah ada jamaah atas nama',
            'apakah ada nama jamaah',
            'cari jamaah atas nama',
            'terdaftar atas nama',
            'status pendaftaran',
            'jamaah bernama',
            'status jamaah',
            'atas nama',
            'cek jamaah',
            'sudah terdaftar',
            'sudah daftar',
            'apakah nama',
            'cek nama',
            'a.n',
        ];
        $needle = trim(preg_replace('/\s+/', ' ', str_replace($stripPhrases, '', $needle)));
        if ($needle === '')
            return [];

        return collect($jamaah)->filter(function ($j) use ($needle) {
            if (empty($j['nama']))
                return false;
            $nama = Str::lower($j['nama']);
            return Str::contains($nama, $needle) || Str::contains($needle, $nama);
        })->values()->all();
    }

    private function loadJamaahContext(): array
    {
        return Cache::remember('wa_bot_jamaah_data', now()->addHours(6), function () {
            $files = glob(base_path('data_jamaah/jamaah_*.json'));
            if (empty($files)) {
                Log::warning('Tidak ada file jamaah_*.json ditemukan di data_jamaah/');
                return [];
            }

            $allJamaah = [];
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if (is_array($data))
                    $allJamaah = array_merge($allJamaah, $data);
            }

            return array_map(function ($j) {
                return [
                    'nama' => $j['nama_jamaah'] ?? ($j['nama'] ?? null),
                    'nama_program' => $j['paket'] ?? ($j['nama_program'] ?? null),
                ];
            }, $allJamaah);
        });
    }

    private function isPaketRelated(string $message): bool
    {
        $text = Str::lower($message);
        $keywords = [
            'paket',
            'tersedia',
            'ketersediaan',
            'kosong',
            'available',
            'seat',
            'kursi',
            'sisa',
            'harga',
            'biaya',
            'bayar',
            'cicilan',
            'quad',
            'triple',
            'double',
            'promo',
            'jadwal',
            'berangkat',
            'keberangkatan',
            'tanggal',
            'bulan',
            'kapan',
            'maskapai',
            'pesawat',
            'garuda',
            'lion',
            'saudia',
            'scoot',
            'batik',
            'transit',
            'rute',
            'hotel',
            'kamar',
            'madinah',
            'makkah',
            'mekkah',
            'malam',
            'program',
            'hari',
            'lama',
        ];
        return Str::contains($text, $keywords);
    }

    private function loadFaqContext(): array
    {
        return Cache::remember('wa_bot_faq_data', now()->addHours(6), function () {
            $path = base_path('faq_1.json');
            if (!file_exists($path)) {
                Log::warning('faq_1.json tidak ditemukan');
                return [];
            }
            return json_decode(file_get_contents($path), true) ?? [];
        });
    }

    private function loadPaketContext(): array
    {
        return Cache::remember('wa_bot_paket_data', now()->addMinutes(5), function () {
            $response = Http::timeout(10)->get('https://absennamiroh.alhidayah.id/api/get-paket', [
                'key' => 'namiroh123#',
            ]);
            if ($response->failed()) {
                Log::error('Gagal ambil data paket', ['status' => $response->status()]);
                return [];
            }
            return $response->json('data', []);
        });
    }

    private function isJamaahListRequest(string $message): bool
    {
        $text = Str::lower($message);
        $keywords = [
            'apakah ada nama jamaah',
            'list jamaah pada paket',
            'daftar jamaah pada paket',
            'list jamaah',
            'daftar jamaah',
            'daftar nama jamaah',
            'list peserta',
            'daftar peserta',
            'siapa saja yang terdaftar',
        ];
        return Str::contains($text, $keywords);
    }

    private function buildJamaahListReply(string $originalQuery): string
    {
        $jamaah = $this->loadJamaahContext();
        if (empty($jamaah))
            return 'Maaf, data jamaah belum tersedia saat ini.';

        $text = Str::lower($originalQuery);
        $filtered = collect($jamaah)->filter(function ($j) use ($text) {
            return $j['nama_program'] && Str::contains($text, Str::lower($j['nama_program']));
        })->values();

        if ($filtered->isEmpty()) {
            return 'Mohon sebutkan nama paket yang dimaksud, misal: "daftar jamaah paket AN NAMIROH".';
        }

        $lines = $filtered->map(fn($j, $i) => ($i + 1) . '. ' . ($j['nama'] ?? '-') . ' — ' . ($j['nama_program'] ?? '-'));
        return "Berikut daftar jamaah:\n" . $lines->implode("\n");
    }

    // ======================= WEBHOOK UTAMA (MEKARI) =======================

    /**
     * Generate HMAC Signature untuk otentikasi API Mekari (Qontak)
     */
    private function generateMekariHmacHeaders(string $method, string $pathWithQuery): array
    {
        $clientId = config('mekari.client_id');
        $clientSecret = config('mekari.client_secret');

        // Waktu wajib dalam format RFC 7231
        $datetime = \Carbon\Carbon::now()->toRfc7231String();

        // Request line format: METHOD /path HTTP/1.1
        $requestLine = strtoupper($method) . " " . $pathWithQuery . " HTTP/1.1";

        // Payload untuk di-hash: "date: {datetime}\n{requestLine}"
        $payload = implode("\n", ["date: {$datetime}", $requestLine]);

        // Generate HMAC SHA256
        $digest = hash_hmac('sha256', $payload, $clientSecret, true);
        $signature = base64_encode($digest);

        $authHeader = 'hmac username="' . $clientId . '", algorithm="hmac-sha256", headers="date request-line", signature="' . $signature . '"';

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Date' => $datetime,
            'Authorization' => $authHeader,
        ];
    }

    public function chatbotApi(Request $request)
    {
        Log::info('Mekari Chatbot API masuk', [
            'body' => $request->all()
        ]);

        $question = $request->input('question');

        if (!$question) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter question wajib diisi.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'question' => $question,
            'answer' => 'Saya menerima pertanyaan: ' . $question
        ]);
    }


    public function handleMekari(Request $request)
    {
        Log::info('Mekari webhook masuk', [
            'raw_body' => $request->getContent(),
            'parsed_input' => $request->all(),
        ]);

        $payload = $request->all();
        $sender = null;
        $message = null;
        $channelId = $payload['channel_id'] ?? null;

        // PARSER WEBHOOK MEKARI / QONTAK
        // Qontak umumnya mengirim format 'from' dan 'message.body'
        if (isset($payload['from']) && isset($payload['message']['body'])) {
            $sender = $payload['from'];
            $message = $payload['message']['body'];
        }
        // Fallback jika Mekari meneruskan format asli Meta (WhatsApp Cloud API)
        elseif (isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
            $waMessage = $payload['entry'][0]['changes'][0]['value']['messages'][0];
            $sender = $waMessage['from'] ?? null;
            $message = $waMessage['text']['body'] ?? null;
            $channelId = $payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? $channelId;
        }
        // Fallback format custom / lainnya
        else {
            $sender = $payload['sender'] ?? ($payload['wa_id'] ?? null);
            $message = $payload['message'] ?? ($payload['text'] ?? null);
        }

        if (!$sender || !$message) {
            Log::warning('Webhook Mekari diabaikan: sender/message kosong', ['body' => $request->getContent()]);
            return response()->json(['status' => 'ignored']);
        }

        $mekariConfig = $this->resolveMekariConfig($channelId);
        $pendingKey = 'wa_pending_jamaah_list_' . $this->normalizeNumber($sender);
        $pending = Cache::get($pendingKey);

        if ($pending) {
            Cache::forget($pendingKey);
            if (trim($message) === 'namiroh2002') {
                $this->sendMekariMessage($sender, $this->buildJamaahListReply($pending['query']), $mekariConfig);
            } else {
                $this->sendMekariMessage($sender, 'Maaf, password salah.', $mekariConfig);
            }
            return response()->json(['status' => 'success']);
        }

        if ($this->isJamaahListRequest($message)) {
            Cache::put($pendingKey, ['query' => $message], now()->addMinutes(5));
            $this->sendMekariMessage($sender, 'Untuk melihat daftar jamaah, mohon masukkan password terlebih dahulu 🙏', $mekariConfig);
            return response()->json(['status' => 'success']);
        }

        try {
            $context = "=== FAQ UMUM NAMIROH TOUR ===\n"
                . json_encode($this->loadFaqContext(), JSON_UNESCAPED_UNICODE) . "\n\n";

            if ($this->isJamaahNameQuery($message)) {
                $matches = $this->findJamaahByNameFuzzy($message);
                if (!empty($matches)) {
                    $context .= "=== HASIL PENCARIAN JAMAAH (sudah difilter sesuai nama yang ditanyakan) ===\n" . json_encode($matches, JSON_UNESCAPED_UNICODE) . "\n\n";
                } else {
                    $context .= "=== HASIL PENCARIAN JAMAAH ===\nTidak ditemukan jamaah dengan nama tersebut di data.\n\n";
                }
            }

            if ($this->isPaketRelated($message)) {
                $paketData = $this->loadPaketContext();
                if (!empty($paketData)) {
                    $context .= "=== DATA PAKET UMROH (real-time, hari ini: " . now()->toDateString() . ") ===\n" . json_encode($paketData, JSON_UNESCAPED_UNICODE) . "\n\n";
                }
            }

            $systemPrompt = "Anda adalah Customer Service AI yang ramah dari Namiroh Tour.\n"
                . "Tugas Anda menjawab pertanyaan jamaah menggunakan data di bawah ini.\n\n" . $context
                . "ATURAN SUMBER DATA:\n"
                . "- Jawab HANYA berdasarkan data di atas, jangan mengarang informasi apa pun.\n"
                . "- Untuk pertanyaan soal harga, jadwal, maskapai, hotel, atau ketersediaan kursi, gunakan bagian DATA PAKET UMROH jika tersedia.\n"
                . "- Untuk pertanyaan umum soal dokumen, cara daftar, visa, atau pelunasan, gunakan bagian FAQ UMUM.\n"
                . "- Kalau field 'available' pada suatu paket bernilai 0  paket itu FULL/tidak tersedia, kalau negatif masih tersedia.\n"
                . "- Kalau ada beberapa paket yang relevan, tampilkan maksimal 3 opsi paling sesuai, jangan semua sekaligus.\n\n"
                . "ATURAN REKOMENDASI UNTUK LANSIA/ORANG TUA:\n"
                . "- Prioritaskan paket dengan program_hari (durasi) LEBIH PENDEK, dan harga paling mahal .\n"
                . "- Di antara paket dengan durasi yang mirip, prioritaskan yang harga (field harga di details_hotels) LEBIH TINGGI — pada data ini, hotel yang lebih mahal umumnya lebih dekat ke Masjidil Haram/Masjid Nabawi sehingga jamaah lebih sedikit berjalan kaki.\n"
                . "- Sebutkan singkat alasan rekomendasinya, misal: \"durasi lebih singkat jadi tidak terlalu melelahkan, hotelnya juga tergolong lebih dekat ke masjid.\"\n"
                . "- PENTING: ini perkiraan dari pola harga, BUKAN data jarak yang benar-benar terukur — jangan sebutkan angka jarak spesifik (meter/menit jalan kaki) yang tidak ada di data.\n\n"
                . "ATURAN JARAK HOTEL KE MASJID:\n"
                . "- Anda bisa akses internet/Google Maps, untuk cari nama hotel_mekkah -> masjidil haram, dan hotel_madinah -> masjid nabawi .\n"
                . "- JANGAN PERNAH mengarang angka jarak (meter, kilometer, atau menit jalan kaki) untuk hotel manapun.\n"
                . "- Kalau ditanya jarak hotel ke Masjidil Haram/Masjid Nabawi analisis dengan google maps jaraknya atau berfikir analisis sendiri:\n"
                . "- Jika jawabannya benar-benar tidak ada di data manapun di atas, balas PERSIS dengan kalimat berikut, tanpa tambahan apa pun: \"" . self::NO_ANSWER_TEXT . "\"\n"
                . "- Gunakan Bahasa Indonesia yang ramah dan sopan.\n"
                . "- Format harga pakai \"Rp\" dan titik ribuan.\n"
                . "- Untuk teks tebal, gunakan SATU tanda bintang (*contoh*) sesuai format WhatsApp — JANGAN dua bintang (**contoh**) seperti markdown biasa.\n";

            $geminiModel = 'gemini-2.5-flash';
            $geminiApiKey = env('GEMINI_API_KEY');
            $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiApiKey}";

            $aiResponse = Http::withHeaders(['Content-Type' => 'application/json'])->post($geminiUrl, [
                'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents' => [['role' => 'user', 'parts' => [['text' => $message]]]],
                'generationConfig' => ['temperature' => 0.3],
            ]);

            $aiResult = $aiResponse->json();

            if (!isset($aiResult['candidates'][0]['content']['parts'][0]['text'])) {
                Log::error('Gemini tidak mengembalikan candidates', ['raw_response' => $aiResult]);
            }

            $balasanAI = $aiResult['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, sistem AI kami sedang sibuk. Coba lagi nanti.';

            if (trim($balasanAI) === self::NO_ANSWER_TEXT) {
                Log::info('Pertanyaan tidak terjawab oleh AI', ['sender' => $sender, 'message' => $message]);
            }

            $this->sendMekariMessage($sender, $balasanAI, $mekariConfig);
            return response()->json(['status' => 'success']);

        } catch (\Throwable $e) {
            Log::error('Error WA Bot Mekari: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->sendMekariMessage($sender, 'Maaf, terjadi gangguan pada server kami saat memproses permintaan Anda.', $mekariConfig);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ======================= HELPER MEKARI =======================

    private function resolveMekariConfig(?string $channelId): array
    {
        return [
            'token' => config('mekari.token'),
            'channel_id' => $channelId ?: config('mekari.channel_id'),
            'api_url' => config('mekari.api_url'),
        ];
    }

    private function normalizeNumber(?string $number): string
    {
        return preg_replace('/\D/', '', (string) $number);
    }

    private function stripPhoneNumbers(string $text): string
    {
        $text = preg_replace('/(?:\+?62|0)8[\d\-\.\s]{6,13}\d/', '[nomor disembunyikan]', $text);
        $text = preg_replace('/\b\d{9,}\b/', '[nomor disembunyikan]', $text);
        return $text;
    }

    /**
     * Mengirim pesan balasan ke Mekari Qontak API
     */
    /**
     * Mengirim pesan balasan AI ke user via API Mekari
     */
    private function sendMekariMessage(string $target, string $message, array $config)
    {
        $message = $this->stripPhoneNumbers($message);
        $target = $this->normalizeNumber($target);

        $path = '/qontak/chat/v1/broadcasts/whatsapp/direct';

        $payload = [
            'channel_id' => config('mekari.channel_id'), // Otomatis ambil dari MEKARI_WA_CHANNEL_ID
            'to' => $target,
            'type' => 'text',
            'message_body' => $message,
        ];

        // Generate Header HMAC khusus untuk request POST ini
        $headers = $this->generateMekariHmacHeaders('POST', $path);

        $response = Http::withHeaders($headers)
            ->timeout(15)
            ->post(config('mekari.api_url') . $path, $payload);

        Log::info('Mekari send response', [
            'target' => $target,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return $response;
    }
}