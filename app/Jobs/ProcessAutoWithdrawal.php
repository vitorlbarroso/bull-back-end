<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessAutoWithdrawal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de tentativas
     */
    public $tries = 3;

    /**
     * Tempo máximo de execução do job
     */
    public $timeout = 60;

    /**
     * Número de segundos para esperar antes de tentar novamente
     */
    public $backoff = 60;

    protected $withdrawalId;
    protected $xApiToken;

    public function __construct($withdrawalId, $xApiToken)
    {
        $this->withdrawalId = $withdrawalId;
        $this->xApiToken = $xApiToken;
        
        // Define a fila específica para este job
        $this->onQueue('withdrawals');
    }

    public function handle()
    {
        try {
            $headers = [
                'Content-Type' => 'application/json'
            ];

            $body = [
                'withdrawal_id' => $this->withdrawalId,
                'x_api_token' => $this->xApiToken,
            ];

            $sendAutoApprove = Http::WithHeaders($headers)
                ->timeout(30)
                ->retry(3, 100)
                ->post(
                    env('ADMIN_BASE_URL') . '/system/wdal/wdal_update',
                    $body
                );

            $response = $sendAutoApprove->json();

            if (!$sendAutoApprove->successful()) {
                Log::error('Falha na requisição de autowithdrawal', [
                    'status' => $sendAutoApprove->status(),
                    'response' => $response,
                    'withdrawal_id' => $this->withdrawalId
                ]);
                throw new \Exception('Falha na requisição de autowithdrawal');
            }

            Log::info('Resposta da requisição para autowithdrawal recebida: ', ['response' => $response]);
        } catch (\Exception $e) {
            Log::error('Erro na requisição de autowithdrawal: ' . $e->getMessage(), [
                'withdrawal_id' => $this->withdrawalId,
                'exception' => $e
            ]);
            
            // Se falhar, lança a exceção para que o job seja reenfileirado
            throw $e;
        }
    }

    /**
     * Método chamado quando o job falha
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Job de autowithdrawal falhou definitivamente', [
            'withdrawal_id' => $this->withdrawalId,
            'exception' => $exception->getMessage()
        ]);
    }
} 