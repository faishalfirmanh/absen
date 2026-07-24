<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
class WaBootController extends Controller
{
    use ApiResponse;


    public function handle(Request $request)
    {
        // 1. Ambil data dari Webhook Fonnte
        Log::info('Fonnte webhook masuk', [
            'raw_body' => $request->getContent(),
            'parsed_input' => $request->all(),
        ]);
        $sender = $request->input('sender');
        $message = $request->input('message');

        if (!$sender || !$message) {
            $raw = json_decode($request->getContent(), true);
            $sender = $sender ?: ($raw['sender'] ?? null);
            $message = $message ?: ($raw['message'] ?? null);
        }

        if (!$sender || !$message) {
            Log::warning('Webhook Fonnte diabaikan: sender/message kosong', [
                'body' => $request->getContent(),
            ]);
            return response()->json(['status' => 'ignored']);
        }

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

            // Model gemini-1.5-pro sudah retired (shutdown), ganti ke model yang masih aktif
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

            // Kalau candidates kosong, log respons mentahnya supaya ketahuan penyebabnya
            // (model deprecated lagi, quota habis, safety block, dll) tanpa harus nebak
            if (!isset($aiResult['candidates'][0]['content']['parts'][0]['text'])) {
                Log::error('Gemini tidak mengembalikan candidates', [
                    'http_status' => $aiResponse->status(),
                    'raw_response' => $aiResult,
                ]);
            }

            $balasanAI = $aiResult['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, sistem AI kami sedang sibuk. Coba lagi nanti.';

            // 4. Kirim Balasan ke Jamaah via Fonnte
            $this->sendFonnteMessage($sender, $balasanAI);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Error WA Bot: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->sendFonnteMessage($sender, 'Maaf, terjadi gangguan pada server kami saat memproses permintaan Anda.');
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    // Fungsi bantuan untuk mengirim pesan ke Fonnte
    private function sendFonnteMessage($target, $message)
    {
        $response = Http::withHeaders([
            'Authorization' => env('FONNTE_TOKEN'),
        ])->post('https://api.fonnte.com/send', [
                    'target' => $target,
                    'message' => $message,
                ]);

        // Log respons Fonnte supaya kelihatan kalau ada error
        // (token salah, format target salah, kuota habis, dll)
        Log::info('Fonnte send response', [
            'target' => $target,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return $response;
    }



    //v2
    public function handleV2(Request $request)
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

    /**
     * Cari token Fonnte yang sesuai dengan nomor device (CS) penerima chat.
     * Kalau device kosong / tidak ada di mapping, fallback ke token default.
     */
    private function resolveToken(?string $device): string
    {
        $devices = collect(config('fonnte.devices', []))
            ->filter(fn($token, $number) => filled($number) && filled($token))
            ->mapWithKeys(fn($token, $number) => [$this->normalizeNumber($number) => $token]);

        if ($device) {
            $token = $devices->get($this->normalizeNumber($device));
            if ($token) {
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