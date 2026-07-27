<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class WaBootController extends Controller
{
    use ApiResponse;

    private const NO_ANSWER_TEXT = 'Mohon maaf, untuk pertanyaan ini kami belum memiliki jawabannya. Tim CS kami akan segera membantu Anda 🙏';


    //jamaah
    private function isJamaahNameQuery(string $message): bool
    {
        $text = Str::lower($message);

        $keywords = [
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

    private function loadJamaahContext(): array
    {
        return Cache::remember('wa_bot_jamaah_data', now()->addHours(6), function () {
            // glob, bukan nama file statis — supaya bulan depan tinggal taruh
            // jamaah_agustus_2026.json di folder yang sama tanpa ubah kode
            $files = glob(base_path('data_jamaah/jamaah_*.json'));

            if (empty($files)) {
                Log::warning('Tidak ada file jamaah_*.json ditemukan di data_jamaah/');
                return [];
            }

            $allJamaah = [];
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if (is_array($data)) {
                    $allJamaah = array_merge($allJamaah, $data);
                } else {
                    Log::warning('Gagal parse file jamaah: ' . basename($file));
                }
            }

            return $allJamaah;
        });
    }
    //jamaah
    private function isPaketRelated(string $message): bool
    {
        $text = Str::lower($message);

        $keywords = [
            // ketersediaan & seat
            'paket',
            'tersedia',
            'ketersediaan',
            'kosong',
            'available',
            'seat',
            'kursi',
            'sisa',
            // harga
            'harga',
            'biaya',
            'bayar',
            'cicilan',
            'quad',
            'triple',
            'double',
            'promo',
            // jadwal
            'jadwal',
            'berangkat',
            'keberangkatan',
            'tanggal',
            'bulan',
            'kapan',
            // maskapai
            'maskapai',
            'pesawat',
            'garuda',
            'lion',
            'saudia',
            'scoot',
            'batik',
            'transit',
            'rute',
            // hotel
            'hotel',
            'kamar',
            'madinah',
            'makkah',
            'mekkah',
            'malam',
            // durasi/program
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
                Log::warning('faq_1.json tidak ditemukan di ' . $path . " \n");
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
                Log::error('Gagal ambil data paket ' . " \n", ['status' => $response->status()]);
                return [];
            }

            return $response->json('data', []);
        });
    }

    //v2-old
    public function handleVold(Request $request)
    {
        // 1. Ambil data dari Webhook Fonnte
        Log::info('Fonnte webhook masuk', [
            'raw_body' => $request->getContent(),
            'parsed_input' => $request->all(),
        ]);

        $sender = $request->input('sender');
        $message = $request->input('message');
        $device = $request->input('device'); // nomor CS yang menerima chat ini

        if (!$sender || !$message || !$device) {
            $raw = json_decode($request->getContent(), true);
            $sender = $sender ?: ($raw['sender'] ?? null);
            $message = $message ?: ($raw['message'] ?? null);
            $device = $device ?: ($raw['device'] ?? null);
        }

        if (!$sender || !$message) {
            Log::warning('Webhook Fonnte diabaikan: sender/message kosong', [
                'body' => $request->getContent(),
            ]);
            return response()->json(['status' => 'ignored']);
        }

        // Tentukan token pengirim berdasarkan nomor CS yang menerima chat
        $token = $this->resolveToken($device);

        try {
            // 2. Hit API untuk mendapatkan JSON Paket
            $paketResponse = Http::get('https://absennamiroh.alhidayah.id/api/get-paket', [
                'key' => 'namiroh123#'
            ]);

            $dataPaket = $paketResponse->json();
            $stringDataPaket = json_encode($dataPaket);

            // 3. Siapkan Prompt untuk AI (Gemini)
            $systemPrompt = "Anda adalah Customer Service AI yang ramah dari Namiroh Tour. " .
                "Tugas Anda menjawab pertanyaan jamaah mengenai paket umrah/tour. " .
                "Gunakan HANYA data JSON berikut sebagai sumber informasi: " . $stringDataPaket . ". " .
                "Jika ada jamaah bertanya yang jawabannya tidak ada di JSON tersebut, " .
                "mohon maaf dan katakan bahwa Anda belum memiliki informasi tersebut.";

            $geminiModel = 'gemini-2.5-flash';
            $geminiApiKey = env('GEMINI_API_KEY');
            $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiApiKey}";

            $aiResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($geminiUrl, [
                        'systemInstruction' => [
                            'parts' => [
                                ['text' => $systemPrompt]
                            ]
                        ],
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [
                                    ['text' => $message]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.7
                        ]
                    ]);

            $aiResult = $aiResponse->json();

            if (!isset($aiResult['candidates'][0]['content']['parts'][0]['text'])) {
                Log::error('Gemini tidak mengembalikan candidates', [
                    'http_status' => $aiResponse->status(),
                    'raw_response' => $aiResult,
                ]);
            }

            $balasanAI = $aiResult['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, sistem AI kami sedang sibuk. Coba lagi nanti.';

            // 4. Kirim Balasan ke Jamaah via Fonnte, dari device yang sama dengan yang dia chat
            $this->sendFonnteMessageV2($sender, $balasanAI, $token);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Error WA Bot: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->sendFonnteMessageV2($sender, 'Maaf, terjadi gangguan pada server kami saat memproses permintaan Anda.', $token);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }


    public function handleV2(Request $request)
    {
        // 1. Ambil data dari Webhook Fonnte
        Log::info('Fonnte webhook masuk ' . " \n", [
            'raw_body' => $request->getContent(),
            'parsed_input' => $request->all(),
        ]);

        $sender = $request->input('sender');
        $message = $request->input('message');
        $device = $request->input('device'); // nomor CS yang menerima chat ini

        if (!$sender || !$message || !$device) {
            $raw = json_decode($request->getContent(), true);
            $sender = $sender ?: ($raw['sender'] ?? null);
            $message = $message ?: ($raw['message'] ?? null);
            $device = $device ?: ($raw['device'] ?? null);
        }

        if (!$sender || !$message) {
            Log::warning('Webhook Fonnte diabaikan: sender/message kosong ' . " \n", [
                'body' => $request->getContent(),
            ]);
            return response()->json(['status' => 'ignored']);
        }

        // Tentukan token pengirim berdasarkan nomor CS yang menerima chat
        $token = $this->resolveToken($device);

        try {
            // 2. Susun context secara dinamis
            $context = "=== FAQ UMUM NAMIROH TOUR ===\n"
                . json_encode($this->loadFaqContext(), JSON_UNESCAPED_UNICODE)
                . "\n\n";


            //jamaah
            if ($this->isJamaahNameQuery($message)) {
                $jamaahData = $this->loadJamaahContext();
                if (!empty($jamaahData)) {
                    $context .= "=== DATA JAMAAH TERDAFTAR ===\n"
                        . json_encode($jamaahData, JSON_UNESCAPED_UNICODE)
                        . "\n\n";
                } else {
                    Log::warning('Pertanyaan terdeteksi soal nama jamaah tapi data jamaah kosong', [
                        'message' => $message,
                    ]);
                }
            }
            //jamaah


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

            // 3. Siapkan Prompt untuk AI (Gemini)
            $systemPrompt = "Anda adalah Customer Service AI yang ramah dari Namiroh Tour.\n"
                . "Tugas Anda menjawab pertanyaan jamaah menggunakan data di bawah ini.\n\n"
                . $context
                . "ATURAN WAJIB:\n"
                . "- Jawab HANYA berdasarkan data di atas, jangan mengarang informasi apa pun.\n"
                . "- Untuk pertanyaan soal harga, jadwal, maskapai, hotel, atau ketersediaan kursi, gunakan bagian DATA PAKET UMROH jika tersedia.\n"
                . "- Untuk pertanyaan umum soal dokumen, cara daftar, visa, atau pelunasan, gunakan bagian FAQ UMUM.\n"
                . "- Kalau ada beberapa paket yang relevan, tampilkan maksimal 3 opsi paling sesuai, jangan semua sekaligus.\n"
                . "- Jika jawabannya benar-benar tidak ada di data manapun di atas, balas PERSIS dengan kalimat berikut, tanpa tambahan apa pun: \""
                . self::NO_ANSWER_TEXT . "\"\n"
                . "- Gunakan Bahasa Indonesia yang ramah dan sopan, format harga pakai \"Rp\" dan titik ribuan.";

            $geminiModel = 'gemini-2.5-flash';
            $geminiApiKey = env('GEMINI_API_KEY');
            $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiApiKey}";

            $aiResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($geminiUrl, [
                        'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                        'contents' => [
                            ['role' => 'user', 'parts' => [['text' => $message]]],
                        ],
                        'generationConfig' => ['temperature' => 0.3], // diturunkan dari 0.7 — buat CS bot, lebih baik konsisten daripada kreatif
                    ]);

            $aiResult = $aiResponse->json();

            if (!isset($aiResult['candidates'][0]['content']['parts'][0]['text'])) {
                Log::error('Gemini tidak mengembalikan candidates ' . " \n", [
                    'http_status' => $aiResponse->status(),
                    'raw_response' => $aiResult,
                ]);
            }

            $balasanAI = $aiResult['candidates'][0]['content']['parts'][0]['text']
                ?? 'Maaf, sistem AI kami sedang sibuk. Coba lagi nanti.';

            if (trim($balasanAI) === self::NO_ANSWER_TEXT) {
                Log::info('Pertanyaan tidak terjawab oleh AI ' . " \n", ['sender' => $sender, 'message' => $message]);
            }

            $this->sendFonnteMessageV2($sender, $balasanAI, $token);
            return response()->json(['status' => 'success']);
        } catch (\Throwable $e) {   // sekalian ganti dari \Exception ke \Throwable seperti dibahas sebelumnya
            Log::error('Error WA Bot: ' . $e->getMessage() . " \n", ['trace' => $e->getTraceAsString()]);
            $this->sendFonnteMessageV2($sender, 'Maaf, terjadi gangguan pada server kami saat memproses permintaan Anda.', $token);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }


    private function resolveToken(?string $device)
    {
        $devices = collect(config('fonnte.devices', []))
            ->filter(fn($token, $number) => filled($number) && filled($token))
            ->mapWithKeys(fn($token, $number) => [$this->normalizeNumber($number) => $token]);

        Log::info('Fonnte resolveToken debug', [
            'device_masuk' => $device,
            'device_normalized' => $device ? $this->normalizeNumber($device) : null,
            'mapped_devices' => $devices->keys()->all(), // cuma nomor, token tidak di-log
        ]);

        if ($device) {
            $token = $devices->get($this->normalizeNumber($device));
            if ($token) {
                Log::info('Fonnte resolveToken: matched', ['device' => $device]);
                return $token;
            }
            Log::warning('Fonnte webhook: device tidak ada di mapping, pakai token default', [
                'device' => $device,
            ]);
        }

        return config('fonnte.default_token');
    }

    private function normalizeNumber(?string $number): string
    {
        return preg_replace('/\D/', '', (string) $number);
    }

    // Fungsi bantuan untuk mengirim pesan ke Fonnte
    private function sendFonnteMessageV2($target, $message, ?string $token = null)
    {
        $response = Http::withHeaders([
            'Authorization' => $token ?: config('fonnte.default_token'),
        ])->post('https://api.fonnte.com/send', [
                    'target' => $target,
                    'message' => $message,
                ]);

        Log::info('Fonnte send response', [
            'target' => $target,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return $response;
    }
    public function tesSend($target, $message)
    {
        $cek = Http::withHeaders([
            'Authorization' => env('FONNTE_TOKEN'),
        ])->post('https://api.fonnte.com/send', [
                    'target' => $target,
                    'message' => $message,
                ]);
        return $this->autoResponse($cek);
    }
}