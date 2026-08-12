<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class MekariAuthService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('mekari.api_url');
        $this->clientId = config('mekari.client_id');
        $this->clientSecret = config('mekari.client_secret');
    }

    protected function generateHeaders(string $method, string $pathWithQuery): array
    {
        $datetime = Carbon::now()->toRfc7231String();
        $requestLine = "{$method} {$pathWithQuery} HTTP/1.1";
        $payload = "date: {$datetime}\n{$requestLine}";
        $digest = hash_hmac('sha256', $payload, $this->clientSecret, true);
        $signature = base64_encode($digest);

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Date' => $datetime,
            'Authorization' => "hmac username=\"{$this->clientId}\", algorithm=\"hmac-sha256\", headers=\"date request-line\", signature=\"{$signature}\"",
        ];
    }

    public function post(string $path, array $body = [])
    {
        return Http::withHeaders($this->generateHeaders('POST', $path))
            ->baseUrl($this->baseUrl)
            ->post($path, $body);
    }

    public function get(string $path, array $query = [])
    {
        $pathWithQuery = $query ? $path . '?' . http_build_query($query) : $path;

        return Http::withHeaders($this->generateHeaders('GET', $pathWithQuery))
            ->baseUrl($this->baseUrl)
            ->get($path, $query);
    }

    /**
     * NOTE: path ini rekonstruksi dari referensi SDK resmi Mekari, belum saya
     * verifikasi 100% dari dokumentasi mentah. Cek dulu di menu "Documentation"
     * dashboard developer kamu - kalau beda, tinggal ganti string path di bawah.
     */
    public function sendWhatsappBroadcastDirect(array $payload)
    {
        return $this->post('/qontak/chat/v1/broadcasts/whatsapp/direct', $payload);
    }

    /**
     * Mendaftarkan / mengaktifkan webhook supaya Qontak melakukan POST setiap
     * ada pesan customer masuk ke URL kita. Ini dipanggil sekali saja (atau
     * setiap kali mau ganti URL/konfigurasi) - BUKAN bagian dari flow
     * per-pesan seperti sendWhatsappBroadcastDirect().
     *
     * NOTE: path & nama field payload ini rekonstruksi dari SDK pihak ketiga
     * + overview docs Mekari, belum saya verifikasi 100% dari dokumentasi
     * mentah resmi. Cek dulu di menu "Documentation" dashboard developer
     * kamu - kalau path beda, tinggal ganti string di bawah, sama seperti
     * broadcast direct.
     *
     * @param string $webhookUrl  HARUS https, bukan http.
     */
    public function registerMessageInteractionWebhook(
        string $webhookUrl,
        bool $fromCustomer = true,
        bool $fromAgent = false,
        bool $statusMessage = false
    ) {
        return $this->post('/message_interactions', [
            'receive_message_from_customer' => $fromCustomer,
            'receive_message_from_agent' => $fromAgent,
            'status_message' => $statusMessage,
            'url' => $webhookUrl,
        ]);
    }

    /**
     * Mematikan webhook message-interaction yang sudah terdaftar (mis. saat
     * mau ganti URL lama ke URL baru, atau saat mau menonaktifkan sementara).
     */
    public function disableMessageInteractionWebhook(
        string $webhookUrl,
        bool $fromCustomer = false,
        bool $fromAgent = false
    ) {
        return $this->post('/message_interactions', [
            'receive_message_from_customer' => $fromCustomer,
            'receive_message_from_agent' => $fromAgent,
            'url' => $webhookUrl,
        ]);
    }

    /**
     * Cek semua webhook url yang sudah terdaftar - dipakai buat verifikasi
     * setelah register, atau debug kenapa webhook lama tidak jalan.
     */
    public function listRegisteredWebhooks()
    {
        return $this->get('/message_interactions');
    }
}