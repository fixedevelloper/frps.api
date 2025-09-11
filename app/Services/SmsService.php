<?php


namespace App\Services;


use Illuminate\Support\Facades\Http;

class SmsService
{
    public function sendSms( $to, $message): array
    {
        $data = [
            "user" => env('API_SMS_USER'),
            "password" => env('API_SMS_PASSWORD'),
            "senderid" => env('API_SMS_SENDER'),
            "sms" => $message,
            "mobiles" => $to
        ];
        $response = Http::withToken(env('API_SMS_TOKEN'))
            ->post(env('API_SMS_URL'), $data);

        return $response->json();
    }
}
