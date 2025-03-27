<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogRequestResponse
{
    public function handle($request, Closure $next)
    {
        // Se a requisição for para o Horizon, não logue nada
        if ($request->is('horizon') || $request->is('horizon/*')) {
            return $next($request);
        }
        // Gerar um UUID para a transação
        $transactionId = Str::uuid()->toString();
        // Adicionar o UUID ao cabeçalho da requisição
        $request->headers->set('X-Transaction-ID', $transactionId);
        $requestData = $request->all();
        if (isset($requestData['password'])) {
            $requestData['password'] = Hash::make($requestData['password']);
        }
        // Log da requisição
        Log::debug(
            "|Request|". $transactionId. "|".$request->getMethod() ."|". $request->fullUrl() ."|".json_encode($request->headers->all(),JSON_PRETTY_PRINT) ."|".json_encode([$requestData],JSON_PRETTY_PRINT)
        );

        //Log das execuções no banco de dados
        DB::listen(function ($query) use ($transactionId) {
            Log::info("|".$transactionId."|Query executada:", ['sql' => $query->sql, 'bindings' => $query->bindings, 'time' => $query->time]);
        });

        // Processar a requisição
        $response = $next($request);

        // Log da resposta
        Log::info(
            "|Response|". $transactionId. "|".
            // $response->getStatusCode(). "|".
            $response
        );

        return $response;
    }
}
