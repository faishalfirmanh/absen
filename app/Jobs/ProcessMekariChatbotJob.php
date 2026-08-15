<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProcessMekariChatbotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private const NO_ANSWER_TEXT = 'Mohon maaf, untuk pertanyaan ini kami belum memiliki jawabannya. Tim CS kami akan segera membantu Anda 🙏';
    public $timeout = 120; // Worker boleh jalan sampai 2 menit untuk nunggu AI
    public $tries = 1;     // Jangan retry otomatis jika gagal, biar ga spam chat ke user

    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }




    private function sendMekariMessage2Param(string $targetNumber, string $message)
    {
        $message = $this->stripPhoneNumbers($message);
        $targetNumber = $this->normalizeNumber($targetNumber);

        Log::info('Job Mekari: mulai kirim via Template HMAC ProcessMEkariChatBootJob sendMekariMessage2Param', ['to' => $targetNumber]);

        $path = '/v1/messages/whatsapp/bot';//'/qontak/chat/v1/broadcasts/whatsapp/direct';

        // Payload Template
        $payload = [
            'to_name' => 'Jamaah',
            'to_number' => $targetNumber,
            'message_template_id' => config('mekari.template_2'),
            'channel_integration_id' => config('mekari.channel_id'),
            'language' => ['code' => 'id'],
            'parameters' => [
                'body' => [
                    ['key' => '1', 'value_text' => $message, 'value' => $message]
                ]
            ]
        ];

        // Ambil credentials via config() BUKAN env()
        $clientId = config('mekari.client_id_2');
        $clientSecret = config('mekari.client_secret_2');

        if (!$clientId || !$clientSecret) {
            Log::error('Job Mekari: Client ID atau Secret KOSONG! Cek config/services.php');
            return;
        }

        // Generate HMAC Signature
        $datetime = \Carbon\Carbon::now()->toRfc7231String();
        $requestLine = "POST " . $path . " HTTP/1.1";
        $hmacPayload = implode("\n", ["date: {$datetime}", $requestLine]);
        $digest = hash_hmac('sha256', $hmacPayload, $clientSecret, true);
        $signature = base64_encode($digest);
        $authHeader = 'hmac username="' . $clientId . '", algorithm="hmac-sha256", headers="date request-line", signature="' . $signature . '"';

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Date' => $datetime,
            'Authorization' => $authHeader,
        ])->timeout(15)->post(config('services.mekari.base_url') . $path, $payload);

        Log::info('Job Mekari: Response HMAC Broadcast', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);
    }

    public function handle()
    {
        $message = trim($this->payload['text'] ?? '');
        $roomId = $this->payload['room_id'] ?? null;
        $sender = $this->payload['room']['account_uniq_id'] ?? null;
        $messageId = $this->payload['id'] ?? null;

        if ($message === '' || !$roomId)
            return;

        // 1. Deduplikasi (Cegah dobel proses kalau Mekari retry)
        $dedupKey = 'mekari_wh_processed_' . $messageId;
        if (Cache::has($dedupKey))
            return;
        Cache::put($dedupKey, true, now()->addMinutes(10));

        // 2. Logic Pending Password Jamaah
        $pendingKey = 'wa_pending_jamaah_list_' . $this->normalizeNumber($sender);
        $pending = Cache::get($pendingKey);

        if ($pending) {
            Cache::forget($pendingKey);
            $reply = trim($message) === 'namiroh2002'
                ? $this->buildJamaahListReply($pending['query'])
                : 'Maaf, password salah. harap hubungi cs https://wa.me/6282245024032 ,  https://wa.me/6289601296887 
            atau   https://wa.me/6281328745647';
            $this->sendMekariMessage1($roomId, $reply, $sender);
            // $this->sendMekariMessage($roomId, $reply, $sender);
            //$this->sendMekariMessage2Param($sender, $reply);
            return;
        }

        if ($this->isJamaahListRequest($message)) {
            Cache::put($pendingKey, ['query' => $message], now()->addMinutes(5));
            // $this->sendMekariMessage($roomId, 'Untuk melihat daftar jamaah, mohon masukkan password terlebih dahulu 🙏',$sender);
            //  $this->sendMekariMessage2Param($sender, 'untuk melihad daftar masukkan passowrd');
            $this->sendMekariMessage1($roomId, 'Untuk melihat daftar jamaah, mohon masukkan password terlebih dahulu 🙏', $sender);
            return;
        }

        // 3. Proses AI Gemini
        try {
            $context = "=== FAQ UMUM NAMIROH TOUR ===\n"
                . json_encode($this->loadFaqContext(), JSON_UNESCAPED_UNICODE) . "\n\n";

            if ($this->isJamaahNameQuery($message)) {
                $matches = $this->findJamaahByNameFuzzy($message);
                if (!empty($matches)) {
                    $context .= "=== HASIL PENCARIAN JAMAAH (sudah difilter sesuai nama yang ditanyakan) ===\n"
                        . json_encode($matches, JSON_UNESCAPED_UNICODE)
                        . "\n\n";
                } else {
                    $context .= "=== HASIL PENCARIAN JAMAAH ===\n" . json_encode($matches, JSON_UNESCAPED_UNICODE) . "Tidak ditemukan jamaah dengan nama tersebut di data.\n\n";
                    Log::warning('Pertanyaan nama jamaah tidak ada match di PHP filter', ['message' => $message]);
                }
                // $context .= !empty($matches)
                //     ? "=== HASIL PENCARIAN JAMAAH ===\n" . json_encode($matches, JSON_UNESCAPED_UNICODE) . "\n\n"
                //     : "=== HASIL PENCARIAN JAMAAH ===\nTidak ditemukan jamaah dengan nama tersebut di data.\n\n";
            }

            if ($this->isPaketRelated($message)) {
                $paketData = $this->loadPaketContext();
                if (!empty($paketData)) {
                    $context .= "=== DATA PAKET UMROH (real-time, hari ini: " . now()->toDateString() . ") ===\n"
                        . json_encode($paketData, JSON_UNESCAPED_UNICODE)
                        . "\n\n";
                } else {
                    Log::warning('Pertanyaan terdeteksi soal paket tapi data paket kosong/gagal diambil', [
                        'message' => $message,
                    ]);
                }
            }

            $systemPrompt = "Anda adalah Customer Service AI yang ramah dari Namiroh Tour.\n"
                . "Tugas Anda menjawab pertanyaan jamaah menggunakan data di bawah ini.\n\n"
                . $context
                . "ATURAN SUMBER DATA:\n"
                . "anda ai dengan sumber data unlimited dari google :\n"
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
                . "- Jika jawabannya benar-benar tidak ada di data manapun di atas, balas PERSIS dengan kalimat berikut, tanpa tambahan apa pun: \""
                . self::NO_ANSWER_TEXT . "\"\n"
                . "- Gunakan Bahasa Indonesia yang ramah dan sopan.\n"
                . "- Format harga pakai \"Rp\" dan titik ribuan.\n"
                . "- Untuk teks tebal, gunakan SATU tanda bintang (*contoh*) sesuai format WhatsApp — JANGAN dua bintang (**contoh**) seperti markdown biasa.\n"
                . "\n"
                . "ATURAN FORMAT BALASAN (WAJIB, KHUSUS WHATSAPP):\n"
                . "- WhatsApp TIDAK mendukung bullet list markdown. Tanda \"*\" HANYA dipakai untuk bold, JANGAN dipakai sebagai bullet/poin di awal baris.\n"
                . "- Kalau jawaban berisi LEBIH DARI SATU item (paket, opsi, langkah, dokumen, dll), gunakan list bernomor (1. 2. 3.) dan SETIAP detail item ditulis di baris terpisah (gunakan newline sungguhan), JANGAN digabung jadi satu paragraf panjang.\n"
                . "- Beri SATU baris kosong sebagai pemisah antar item nomor 1, 2, 3, dst supaya mudah dibaca di WhatsApp.\n"
                . "- Untuk sub-detail dalam satu item (tanggal, maskapai, harga, dll), tulis masing-masing di baris sendiri, diawali tanda \"-\" (strip biasa, BUKAN bintang).\n"
                . "- Bahkan untuk jawaban dengan hanya 1 item, kalau informasinya berisi beberapa poin/kalimat, tetap pecah ke beberapa baris — jangan jadi satu paragraf padat tanpa jeda baris.\n"
                . "- Kalimat pembuka dan penutup boleh tetap dalam bentuk paragraf biasa.\n"
                . "Contoh format balasan untuk lebih dari satu paket:\n"
                . "1. *Program AN NAMIROH*\n"
                . "- Tanggal Keberangkatan: 5 September 2026\n"
                . "- Maskapai: GARUDA\n"
                . "- Rute: SUBJED - JEDSUB\n"
                . "- Durasi: 16 hari\n"
                . "- Hotel Madinah: ARKAN GOLDEN (9 malam)\n"
                . "- Hotel Makkah: AL AMEEN AJYAD (5 malam)\n"
                . "- Harga: Mulai dari Rp37.350.000 (Quad)\n"
                . "- Ketersediaan: Tersedia 5 seat\n"
                . "\n"
                . "2. *Program TAJALLI*\n"
                . "- Tanggal Keberangkatan: 15 September 2026\n"
                . "- Maskapai: LION\n"
                . "- ...(dst)\n";


            // PENTING: Gunakan config() bukan env() di dalam Job
            $geminiModel = 'gemini-2.5-flash';
            $geminiApiKey = env('GEMINI_API_KEY');
            $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiApiKey}";

            Log::info('Job Mekari: memanggil Gemini', ['room_id' => $roomId]);

            $aiResponse = Http::timeout(60)->withHeaders(['Content-Type' => 'application/json'])->post($geminiUrl, [
                'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents' => [['role' => 'user', 'parts' => [['text' => $message]]]],
                'generationConfig' => ['temperature' => 0.3],
            ]);

            $aiResult = $aiResponse->json();
            $balasanAI = $aiResult['candidates'][0]['content']['parts'][0]['text']
                ?? 'Mohon maaf, untuk pertanyaan ini kami belum memiliki jawabannya. harap hubungi https://wa.me/6282245024032 ,  https://wa.me/6289601296887 
            atau   https://wa.me/6281328745647 ';

            //$this->sendMekariMessage($roomId, $balasanAI,$sender);
            //  $this->sendMekariMessage2Param($sender, $balasanAI);
            $this->sendMekariMessage1($roomId, $balasanAI, $sender);

        } catch (\Throwable $e) {
            Log::error('Error Job Mekari Chatbot: roomid ' . $roomId . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // $this->sendMekariMessage2Param($sender, 'Maaf, terjadi gangguan pada sistem AI kami.');
            $this->sendMekariMessage1($roomId, 'Maaf, tidak bisa menjawab pertanyaan, harap hubungi https://wa.me/6282245024032 ,  https://wa.me/6289601296887 
            atau   https://wa.me/6281328745647', $sender);
        }
    }

    // ======================= HELPER METHODS =======================
    private function normalizeNumber(?string $number): string
    {
        return preg_replace('/\D/', '', (string) $number);
    }

    private function stripPhoneNumbers(string $text): string
    {
        $text = preg_replace('/(?:\+?62|0)8[\d\-\.\s]{6,13}\d/', '[nomor disembunyikan]', $text);
        return preg_replace('/\b\d{9,}\b/', '[nomor disembunyikan]', $text);
    }

    private function sendMekariMessage1(string $roomId, string $message, string $senderNumber)
    {
        // FIX: sebelumnya semua newline diganti spasi -> itu penyebab balasan jadi 1 paragraf panjang
        // padahal endpoint text WhatsApp mendukung newline dengan baik.
        // Sekarang cukup normalisasi \r\n / \r jadi \n biasa, lalu rapikan baris kosong berlebih.
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $message = preg_replace("/\n{3,}/", "\n\n", $message); // maksimal 1 baris kosong berturut-turut
        $message = trim($message);

        $message = $this->stripPhoneNumbers($message);

        $response = Http::withToken(config('mekari.omnichannel_token'))
            ->timeout(15)
            ->post(config('mekari.chat_base_url') . '/v1/messages/whatsapp', [
                'room_id' => $roomId,
                'type' => 'text',
                'text' => $message,
            ]);

        Log::info('Job Mekari: Response Bot Message', [
            'room_id' => $roomId,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);
    }


    private function sendMekariMessage(string $targetNumber, string $message)
    {
        $message = $this->stripPhoneNumbers($message);
        $targetNumber = $this->normalizeNumber($targetNumber);

        Log::info('Job Mekari: mulai kirim via Template HMAC', ['to' => $targetNumber]);

        // 1. Path API Broadcast Direct (HMAC)
        $path = '/qontak/chat/v1/broadcasts/whatsapp/direct';

        // 2. Payload Template (Memasukkan teks AI ke dalam variabel {{1}})
        $payload = [
            'to_name' => 'Jamaah',
            'to_number' => $targetNumber, // Nomor HP jamaah dari payload webhook
            'message_template_id' => env('MEKARI_TEMPLATE_2'),
            'channel_integration_id' => env('MEKARI_WA_CHANNEL_ID'), // ID Channel WA Anda
            'language' => ['code' => 'id'],
            'parameters' => [
                'body' => [
                    ['key' => '1', 'value_text' => $message, 'value' => $message]
                ]
            ]
        ];

        // 3. Generate HMAC Signature (Sama persis seperti sistem VA Anda)
        $clientId = config('mekari.client_id_2');
        $clientSecret = config('mekari.client_secret_2');
        $datetime = \Carbon\Carbon::now()->toRfc7231String();
        $requestLine = "POST " . $path . " HTTP/1.1";
        $hmacPayload = implode("\n", ["date: {$datetime}", $requestLine]);
        $digest = hash_hmac('sha256', $hmacPayload, $clientSecret, true);
        $signature = base64_encode($digest);
        $authHeader = 'hmac username="' . $clientId . '", algorithm="hmac-sha256", headers="date request-line", signature="' . $signature . '"';

        // 4. Kirim Request
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Date' => $datetime,
            'Authorization' => $authHeader,
        ])->timeout(15)->post(env('MEKARI_API_BASE_URL', 'https://api.mekari.com') . $path, $payload);

        Log::info('Job Mekari: Response HMAC Broadcast', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);
    }

    private function isJamaahNameQuery(string $message): bool
    {
        $text = Str::lower($message);
        $keywords = ['apakah ada nama jamaah', 'list jamaah pada paket', 'daftar jamaah pada paket', 'nama saya', 'atas nama', 'a.n', 'jamaah bernama', 'sudah terdaftar', 'sudah daftar', 'status pendaftaran', 'status jamaah', 'cek nama', 'cek jamaah', 'apakah nama', 'terdaftar atas nama'];
        return Str::contains($text, $keywords);
    }

    private function findJamaahByNameFuzzy(string $message): array
    {
        $jamaah = $this->loadJamaahContext();
        if (empty($jamaah))
            return [];
        $needle = Str::lower($message);
        $stripPhrases = ['apakah ada jamaah atas nama', 'apakah ada nama jamaah', 'cari jamaah atas nama', 'terdaftar atas nama', 'status pendaftaran', 'jamaah bernama', 'status jamaah', 'atas nama', 'cek jamaah', 'sudah terdaftar', 'sudah daftar', 'apakah nama', 'cek nama', 'a.n'];
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
            if (empty($files))
                return [];
            $allJamaah = [];
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if (is_array($data))
                    $allJamaah = array_merge($allJamaah, $data);
            }
            return array_map(function ($j) {
                return ['nama' => $j['nama_jamaah'] ?? ($j['nama'] ?? null), 'nama_program' => $j['paket'] ?? ($j['nama_program'] ?? null)];
            }, $allJamaah);
        });
    }

    private function isPaketRelated(string $message): bool
    {
        $text = Str::lower($message);
        $keywords = ['paket', 'tersedia', 'ketersediaan', 'kosong', 'available', 'seat', 'kursi', 'sisa', 'harga', 'biaya', 'bayar', 'cicilan', 'quad', 'triple', 'double', 'promo', 'jadwal', 'berangkat', 'keberangkatan', 'tanggal', 'bulan', 'kapan', 'maskapai', 'pesawat', 'garuda', 'lion', 'saudia', 'scoot', 'batik', 'transit', 'rute', 'hotel', 'kamar', 'madinah', 'makkah', 'mekkah', 'malam', 'program', 'hari', 'lama'];
        return Str::contains($text, $keywords);
    }

    private function loadFaqContext(): array
    {
        return Cache::remember('wa_bot_faq_data', now()->addHours(6), function () {
            $path = base_path('faq_1.json');
            if (!file_exists($path))
                return [];
            return json_decode(file_get_contents($path), true) ?? [];
        });
    }

    private function loadPaketContext(): array
    {
        return Cache::remember('wa_bot_paket_data', now()->addMinutes(5), function () {
            $response = Http::timeout(10)->get('https://absennamiroh.alhidayah.id/api/get-paket', ['key' => 'namiroh123#']);
            if ($response->failed())
                return [];
            return $response->json('data', []);
        });
    }

    private function isJamaahListRequest(string $message): bool
    {
        $text = Str::lower($message);
        $keywords = ['apakah ada nama jamaah', 'list jamaah pada paket', 'daftar jamaah pada paket', 'list jamaah', 'daftar jamaah', 'daftar nama jamaah', 'list peserta', 'daftar peserta', 'siapa saja yang terdaftar'];
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
        if ($filtered->isEmpty())
            return 'Mohon sebutkan nama paket yang dimaksud, misal: "daftar jamaah paket AN NAMIROH".';
        $lines = $filtered->map(fn($j, $i) => ($i + 1) . '. ' . ($j['nama'] ?? '-') . ' — ' . ($j['nama_program'] ?? '-'));
        return "Berikut daftar jamaah:\n" . $lines->implode("\n");
    }
}