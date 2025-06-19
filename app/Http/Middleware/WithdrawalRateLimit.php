<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class WithdrawalRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $cacheKey = "withdrawal_rate_limit_{$user->id}";
        $lastRequestTime = Cache::get($cacheKey);

        if ($lastRequestTime) {
            $timeSinceLastRequest = time() - $lastRequestTime;
            
            if ($timeSinceLastRequest < 5) {
                $remainingTime = 5 - $timeSinceLastRequest;
                return response()->json([
                    'error' => 'Muitas solicitações. Aguarde ' . $remainingTime . ' segundos antes de tentar novamente.',
                    'remaining_seconds' => $remainingTime,
                    'success' => false
                ], 429);
            }
        }

        // Armazena o timestamp da requisição atual
        Cache::put($cacheKey, time(), 60); // Cache por 1 minuto

        return $next($request);
    }
} 