<?php

use App\Mail\TestMail;
use App\Mail\VerifyEmailMail;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Mail;
use Mailjet\Client;
use Mailjet\Resources;

Route::get('/test-mailjet', function () {
    try {
        Mail::to('rodriguembah13@gmail.com')->send(new TestMail());
        return "Email envoyé avec succès ✔️";
    } catch (\Exception $e) {
        return "Erreur lors de l'envoi ❌ : " . $e->getMessage();
    }
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-mailjet-api', function () {
    $mj = new Client(env('MAIL_USERNAME'), env('MAIL_PASSWORD'), true, [
        'version' => 'v3.1'
    ]);

    $body = [
        'Messages' => [[
            'From' => ['Email' => "info@frps-ad.cm"],
            'To' => [['Email' => "rodriguembah13@gmail.com"]],
            'Subject' => "Test API Mailjet",
            'TextPart' => "Test",
        ]]
    ];

    $response = $mj->post(Resources::$Email, ['body' => $body]);

    return [
        "status" => $response->getStatus(),
        "success" => $response->success(),
        "data" => $response->getData(),
    ];
});
