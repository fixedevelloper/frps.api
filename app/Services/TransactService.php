<?php


namespace App\Services;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransactService
{

    private $token;
    private  $base_url;


    /**
     * TransactService constructor.
     */
    public function __construct()
    {
        $this->base_url = (config('app.env') === 'local')
            ? 'https://sandbox.dsapi.tranzak.me'
            : 'https://dsapi.tranzak.me';
    }
    /**
     * 🔐 Auth
     */
    public function authenticate(): void
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->post($this->base_url . '/auth/token', [
            "appId" => config('services.tranzak.appId'),
            "appKey" => config('services.tranzak.appKey')
        ]);

        logger([
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
        $data_response = $response->json();

        if (($data_response['success'] ?? null)) {
            $data=$data_response['data'];
            Cache::put('tranzak_token', $data['token'], now()->addMinutes(50));
        } else {
            Log::error('TRANZAK AUTH FAILED', $data_response);
        }
    }

    public function makeColletion(array $request_data): array
    {
        $data = [
            'amount' => $request_data['amount'] ?? 0,
            'currencyCode' => 'XAF',
            'description' => $request_data['description'] ?? 'Paiement facture FRPS',
            'mchTransactionRef' => $request_data['reference'] ?? uniqid('PAY-'),
            'returnUrl' => $request_data['success_url'] ?? '',
            'cancelUrl' => $request_data['cancel_url'] ?? '',
            'callbackUrl' => $request_data['callback_url'] ?? ''
        ];

        $response = $this->request('xp021/v1/request/create', $data);

        if (!$response || !isset($response['data'])) {
            throw new \Exception('Erreur lors de la création du paiement');
        }

        return $response;
    }

    public function collectionStatus(array $request_data): array
    {
        if (empty($request_data['requestId'])) {
            throw new \InvalidArgumentException('requestId manquant');
        }

        $data = [
            'requestId' => $request_data['requestId'],
        ];

        $response = $this->request(
            'xp021/v1/request/refresh-transaction-status',
            $data
        );

        if (!$response || !isset($response['success']) || !$response['success']) {
            throw new \Exception('Erreur lors de la récupération du statut de paiement');
        }

        if (!isset($response['data'])) {
            throw new \Exception('Réponse Tranzak invalide');
        }

        return $response['data'];
    }

    /**
     * 🔁 HTTP centralisé
     * @param $endpoint
     * @param array $payload
     * @param string $method
     * @return array
     */
    protected function request($endpoint, array $payload = [],  $method = 'POST')
    {
        Log::info('TRANSAK REQUEST INIT', [
            'endpoint' => $endpoint,
            'method'   => $method,
            'payload'  => $payload,
        ]);
        $this->authenticate();
        $token = Cache::get('tranzak_token');

        if (!$token) {
            Log::warning('TRANSAK TOKEN NOT FOUND - AUTHENTICATION TRIGGERED');
            $this->authenticate();
            $token = Cache::get('tranzak_token');
        }

        $http = Http::withToken($token)
            ->acceptJson()
            ->timeout(30)
            ->retry(2, 200);

        try {

            $url = $this->base_url.'/'.$endpoint;

            $response = $method === 'GET'
                ? $http->get($url, $payload)
                : $http->post($url, $payload);

            Log::info('TRANSAK RESPONSE RAW', [
                'url'         => $url,
                'status_http' => $response->status(),
                'body'        => $response->body(),
            ]);

            $data = $response->json();

            Log::info('TRANSAK RESPONSE PARSED', [
                'endpoint' => $endpoint,
                'response' => $data,
            ]);

            /**
             * 🔄 TOKEN EXPIRED
             */
            if ($response->status() === 401) {

                Log::warning('TRANSAK TOKEN EXPIRED - REFRESHING');

                $this->authenticate();

                return $this->request($endpoint, $payload, $method);
            }

            /**
             * ❌ API BUSINESS ERROR
             */
            if (($data['success'] ?? false) === false) {

                Log::error('TRANSAK BUSINESS ERROR', [
                    'endpoint'  => $endpoint,
                    'payload'   => $payload,
                    'errorMsg'  => $data['errorMsg'] ?? null,
                    'errorCode' => $data['errorCode'] ?? null,
                ]);

                return [
                    'success' => false,
                    'message' => $data['errorMsg'] ?? 'Unknown API error',
                    'code'    => $data['errorCode'] ?? null,
                    'data'    => $data['data'] ?? null
                ];
            }

            /**
             * ✅ SUCCESS RESPONSE
             */
            return [
                'success' => true,
                'data' => $data['data'] ?? [],
                'pagination' => [
                    'totalItems'  => $data['totalItems'] ?? null,
                    'pageSize'    => $data['pageSize'] ?? null,
                    'currentPage' => $data['currentPage'] ?? null,
                    'hasMore'     => $data['hasMore'] ?? null,
                ]
            ];

        } catch (\Throwable $e) {

            Log::error('TRANSAK REQUEST EXCEPTION', [
                'endpoint' => $endpoint,
                'payload'  => $payload,
                'message'  => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Internal error',
            ];
        }
    }
}
