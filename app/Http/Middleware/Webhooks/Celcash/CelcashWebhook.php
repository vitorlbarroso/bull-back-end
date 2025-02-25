<?php

namespace App\Http\Middleware\Webhooks\Celcash;

use App\Http\Helpers\Responses;
use App\Models\CelcashConfirmHashWebhook;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CelcashWebhook
{
    protected $authorizedIps = [
        '127.0.0.1', // Apenas em dev
        '193.186.4.239', // Apenas em dev
        '34.233.88.73',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $senderIp = $request->ip();

        if (!in_array($senderIp, $this->authorizedIps)) {
            try {
                $createLogError = \App\Models\CelcashWebhook::create([
                    'webhook_title' => 'IP NÃO AUTORIZADO',
                    'webhook_id' => $request->input('orderId'),
                    'webhook_event' => $request->input('status'),
                    'webhook_data' => $request,
                    'webhook_sender' => $senderIp,
                ]);
            } catch (\Throwable $th) {
                Log::error('Ocorreu um erro ao salvar um log de crash do webhook!', ['error' => $th->getMessage()]);
            }

            return Responses::ERROR('Ação não autorizada!', null, 1100, 401);
        }

        try {
            $createLog = \App\Models\CelcashWebhook::create([
                'webhook_title' => 'WEBHOOK RECEBIDO',
                'webhook_id' => $request->input('orderId'),
                'webhook_event' => $request->input('status'),
                'webhook_data' => $request,
                'webhook_sender' => $senderIp,
            ]);
        } catch (\Throwable $th) {
            Log::error('Ocorreu um erro ao salvar um log de crash do webhook!', ['error' => $th->getMessage()]);
        }

        return $next($request);
    }
}
