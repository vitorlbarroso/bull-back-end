<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;
class ScheduleRunMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se a última execução foi há mais de uma hora
        if (Cache::has('tokens:delete-expired:last-executed')) {
            $lastExecuted = Cache::get('tokens:delete-expired:last-executed');
            if (now()->diffInMinutes($lastExecuted) >= 60) {
                // Executa o comando se passou uma hora ou mais desde a última execução
                \Artisan::call('tokens:delete-expired');
                Cache::put('tokens:delete-expired:last-executed', now());
            }
        } else {
            // Se não há registro da última execução, executa o comando e armazena a data/hora atual
            \Artisan::call('tokens:delete-expired');
            Cache::put('tokens:delete-expired:last-executed', now());
        }
        return $next($request);
    }
}
