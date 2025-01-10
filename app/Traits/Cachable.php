<?php


namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait Cachable
{
    /**
     * Obter dados do cache ou armazenar e retornar se não existir
     *
     * @param string $Key Chave única para o cache
     * @param callable $callback Função de callback para obter os dados se não estiverem em cache
     * @param int $expiration Tempo de expiração do cache em segundos 600 segundos equivale a 10 minutos
     * @return mixed Dados obtidos do cache ou do callback
     */
    protected function getOrSetCache($transactionId, $key, $callback, $expiration = 600)
    {
        // Verifica se o item está em cache
        if (Cache::has($key)) {
            Log::info('|' . $transactionId."| Buscando informação do Cache",["chave" => $key, "data" => Cache::get($key)]);
            return Cache::get($key);
        }

        // Caso não esteja em cache, executa o callback para obter os dados
        $data = $callback();

        // Armazena os dados em cache pelo tempo de expiração especificado
        Cache::put($key, $data, $expiration);
        Log::info('|' . $transactionId."| Não existe a informação em Cache, adicionada a chave ${key}");
        return $data;
    }

    protected function removeCache($transactionId, $key)
    {
        // Verifica se o item está em cache
        if (Cache::has($key)) {
            // Remove o item do cache
            Cache::forget($key);
            Log::info('|' . $transactionId . "| Removendo informação do Cache", ["chave" => $key]);
        } else {
            Log::info('|' . $transactionId . "| Tentativa de remover chave inexistente do Cache", ["chave" => $key]);
        }
    }

}
