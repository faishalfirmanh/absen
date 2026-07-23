<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaBootController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Ambil data dari Webhook Fonnte
        $sender = $request->input('sender'); // Nomor WA jamaah
        $message = $request->input('message'); // Pesan yang diketik jamaah

        // Abaikan jika tidak ada pesan atau pengirim
        if (!$sender || !$message) {
            return response()->json(['status' => 'ignored']);
        }

        try {
            // 2. Hit API untuk mendapatkan JSON Paket
            $paketResponse = Http::post('https://absennamiroh.alhidayah.id/api/get-paket', [
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

            // URL API Gemini Pro (menggunakan gemini-1.5-pro yang mendukung system instruction)
            $geminiApiKey = env('GEMINI_API_KEY');
            $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key={$geminiApiKey}";

            // Request ke Google Gemini API
            $aiResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($geminiUrl, [
                        // System Instruction (Konteks peran AI)
                        'systemInstruction' => [
                            'parts' => [
                                ['text' => $systemPrompt]
                            ]
                        ],
                        // Pesan dari jamaah
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [
                                    ['text' => $message]
                                ]
                            ]
                        ],
                        // Pengaturan AI
                        'generationConfig' => [
                            'temperature' => 0.7 // 0.0 lebih kaku, 1.0 lebih kreatif
                        ]
                    ]);

            $aiResult = $aiResponse->json();

            // Membaca teks balasan dari struktur JSON Gemini
            $balasanAI = $aiResult['candidates'][0]['content']['parts'][0]['text'] ?? 'Maaf, sistem AI kami sedang sibuk. Coba lagi nanti.';

            // 4. Kirim Balasan ke Jamaah via Fonnte
            $this->sendFonnteMessage($sender, $balasanAI);

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Error WA Bot: ' . $e->getMessage());
            $this->sendFonnteMessage($sender, 'Maaf, terjadi gangguan pada server kami saat memproses permintaan Anda.');
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // Fungsi bantuan untuk mengirim pesan ke Fonnte
    private function sendFonnteMessage($target, $message)
    {
        Http::withHeaders([
            'Authorization' => env('FONNTE_TOKEN'),
        ])->post('https://api.fonnte.com/send', [
                    'target' => $target,
                    'message' => $message,
                ]);
    }
}