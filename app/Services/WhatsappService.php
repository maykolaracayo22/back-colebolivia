<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsappService
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->apiKey = config('services.whatsapp.api_key');
    }

    public function sendMessage($telefono, $mensaje)
    {
        if (!$this->apiUrl || !$this->apiKey) {
            return [
                'success' => false,
                'response' => 'WhatsApp API not configured',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/send', [
                'phone' => $telefono,
                'message' => $mensaje,
            ]);

            return [
                'success' => $response->successful(),
                'response' => $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'response' => $e->getMessage(),
            ];
        }
    }
}
