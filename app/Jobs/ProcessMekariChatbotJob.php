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

    public $timeout = 120; // Worker boleh jalan sampai 2 menit untuk nunggu AI
    public $tries = 1;     // Jangan retry otomatis jika gagal, biar ga spam chat ke user

    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
    
    
     
     
         private function sendMekariMessage2Param(string $targetNumber, string $message) {
        $message = $this->stripPhoneNumbers($message);
        $targetNumber = $this->normalizeNumber($targetNumber);
        
        Log::info('Job Mekari: mulai kirim via Template HMAC', ['to' => $targetNumber]);

        $path = '/qontak/chat/v1/broadcasts/whatsapp/direct';
        
        // Payload Template
        $payload = [
            'to_name'  => 'Jamaah',
            'to_number' => $targetNumber, 
            'message_template_id' => config('services.mekari.ai_template_id'),
            'channel_integration_id' => config('services.mekari.channel_id'), 
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
                : 'Maaf, password salah.';
           // $this->sendMekariMessage($roomId, $reply, $sender);
           $this->sendMekariMessage2Param($sender, $replay);
            return;
        }

        if ($this->isJamaahListRequest($message)) {
            Cache::put($pendingKey, ['query' => $message], now()->addMinutes(5));
            // $this->sendMekariMessage($roomId, 'Untuk melihat daftar jamaah, mohon masukkan password terlebih dahulu 🙏',$sender);
              $this->sendMekariMessage2Param($sender, 'untuk melihad daftar masukkan passowrd');
            return;
        }

        // 3. Proses AI Gemini
        try {
            $context = "=== FAQ UMUM NAMIROH TOUR ===\n"
                . json_encode($this->loadFaqContext(), JSON_UNESCAPED_UNICODE) . "\n\n";

            if ($this->isJamaahNameQuery($message)) {
                $matches = $this->findJamaahByNameFuzzy($message);
                $context .= !empty($matches)
                    ? "=== HASIL PENCARIAN JAMAAH ===\n" . json_encode($matches, JSON_UNESCAPED_UNICODE) . "\n\n"
                    : "=== HASIL PENCARIAN JAMAAH ===\nTidak ditemukan jamaah dengan nama tersebut di data.\n\n";
            }

            if ($this->isPaketRelated($message)) {
                $paketData = $this->loadPaketContext();
                if (!empty($paketData)) {
                    $context .= "=== DATA PAKET UMROH (real-time, hari ini: " . now()->toDateString() . ") ===\n"
                        . json_encode($paketData, JSON_UNESCAPED_UNICODE) . "\n\n";
                }
            }

            $systemPrompt = "Anda adalah Customer Service AI yang ramah dari Namiroh Tour.\n"
                . "Tugas Anda menjawab pertanyaan jamaah menggunakan data di bawah ini.\n\n" . $context
                . "ATURAN: Jawab HANYA berdasarkan data. Jika tidak ada, balas PERSIS: Mohon maaf, untuk pertanyaan ini kami belum memiliki jawabannya. Tim CS kami akan segera membantu Anda 🙏\n"
                . "Gunakan Bahasa Indonesia yang ramah. Format harga pakai Rp dan titik ribuan. Tebal pakai *satu bintang*.";

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
                ?? 'Mohon maaf, untuk pertanyaan ini kami belum memiliki jawabannya. Tim CS kami akan segera membantu Anda 🙏';

            //$this->sendMekariMessage($roomId, $balasanAI,$sender);
            $this->sendMekariMessage2Param($sender,$balasanAI);

        } catch (\Throwable $e) {
            Log::error('Error Job Mekari Chatbot: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->sendMekariMessage($roomId, 'Maaf, terjadi gangguan pada sistem AI kami.',$sender);
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
    $message = str_replace(["\r\n", "\n", "\r"], ' ', $message); // jaga-jaga, walau endpoint ini text biasa harusnya aman newline
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


      private function sendMekariMessage(string $targetNumber, string $message) {
        $message = $this->stripPhoneNumbers($message);
        $targetNumber = $this->normalizeNumber($targetNumber);
        
        Log::info('Job Mekari: mulai kirim via Template HMAC', ['to' => $targetNumber]);

        // 1. Path API Broadcast Direct (HMAC)
        $path = '/qontak/chat/v1/broadcasts/whatsapp/direct';
        
        // 2. Payload Template (Memasukkan teks AI ke dalam variabel {{1}})
        $payload = [
            'to_name'  => 'Jamaah',
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
        $clientId = config('mekari.client_id_2') ;
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