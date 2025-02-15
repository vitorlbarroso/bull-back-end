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
    public function handle(Request $request, Closure $next)
    {
        // Verifica se existe um objeto 'headers' no corpo da requisição
        $headers = $request->input('headers');
        if (!isset($headers['x-auth-key'])) {
            return $this->denyRequest($request, 'X-AUTH-KEY AUSENTE');
        }

        // Obtém o valor esperado do banco de dados
        $expectedAuthKey = CelcashConfirmHashWebhook::where('webhook_event', 'payments_voluti')
            ->value('confirm_hash');

        // Verifica se o x-auth-key é válido
        if ($headers['x-auth-key'] !== $expectedAuthKey) {
            return $this->denyRequest($request, 'X-AUTH-KEY INVÁLIDO');
        }

        // Registra a requisição válida
        $this->logWebhook($request, 'WEBHOOK RECEBIDO');

        return $next($request);
    }

    private function denyRequest(Request $request, string $reason)
    {
        try {
            \App\Models\CelcashWebhook::create([
                'webhook_title' => $reason,
                'webhook_id' => $request->input('id'),
                'webhook_event' => $request->input('data.webhookType'),
                'webhook_data' => json_encode($request->all()),
                'webhook_sender' => $request->ip(),
            ]);
        } catch (\Throwable $th) {
            Log::error('Erro ao salvar log de falha do webhook!', ['error' => $th->getMessage()]);
        }

        return response()->json(['error' => 'Ação não autorizada!'], 401);
    }

    private function logWebhook(Request $request, string $title)
    {
        try {
            \App\Models\CelcashWebhook::create([
                'webhook_title' => $title,
                'webhook_id' => $request->input('id'),
                'webhook_event' => $request->input('data.webhookType'),
                'webhook_data' => json_encode($request->all()),
                'webhook_sender' => $request->ip(),
            ]);
        } catch (\Throwable $th) {
            Log::error('Erro ao salvar log do webhook!', ['error' => $th->getMessage()]);
        }
    }
}
