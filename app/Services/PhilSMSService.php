<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PhilSmsService
{
    protected $apiUrl;
    protected $apiToken;

    public function __construct()
    {
        $this->apiUrl = config('services.philsms.url', env('PHILSMS_API_URL'));
        $this->apiToken = config('services.philsms.token', env('PHILSMS_API_TOKEN'));
    }

    /**
     * Send an SMS message via PhilSMS API.
     *
     * @param string $to Recipient phone number (format: 09xxxxxxxxx or +639xxxxxxxxx)
     * @param string $message The message to send
     * @return array|null
     */
    public function sendSms(string $to, string $message)
    {
        $response = Http::withToken($this->apiToken)
            ->post(rtrim($this->apiUrl, '/') . '/sms/send', [
                'recipient' => $to,
                'message' => $message,
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }
}
