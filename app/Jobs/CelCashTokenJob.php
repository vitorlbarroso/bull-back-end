<?php

namespace App\Jobs;

use App\Models\CelcashPaymentsGatewayData;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CelCashTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->handle();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $galaxId = env('CELCASH_GALAX_ID');
        $galaxHash = env('CELCASH_GALAX_HASH');
        $baseUrl = env('CELCASH_BASE_URL');

        $headers = [
            'Authorization' => 'Basic ' . base64_encode($galaxId . ':' . $galaxHash),
            'Content-Type' => 'application/json',
        ];

        $body = [
            'grant_type' => 'authorization_code',
            'scope' => 'company.write company.read charges.write',
        ];

        try {
            $response = HTTP::withHeaders($headers)->post($baseUrl . "/v2/token", $body);

            $data = $response->json();
            $accessToken = $data['access_token'];
            $expiresIn = Carbon::now()->addMinutes(5);

            CelcashPaymentsGatewayData::updateOrCreate(
                ['id' => 1],
                [
                    'token' => $accessToken,
                    'expires_in' => $expiresIn,
                ]
            );
        }
        catch(\Exception $e) {
            Log::error('Erro ao atualizar token de requisição API: ' . $e->getMessage());
        }
    }
}
