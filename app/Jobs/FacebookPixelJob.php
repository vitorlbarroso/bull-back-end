<?php

namespace App\Jobs;

use App\Events\PixelEvent;
use App\Models\LogsPixelEvent;
use App\Models\OfferPixel;
use App\Services\PixelEventService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FacebookPixelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected PixelEvent $event;
    protected OfferPixel $offerPixel;
    public $tries = 3; // Tentativas de reprocessamento
    public $backoff = 60; // Tempo de espera entre tentativas (60 segundos)

    public function __construct(PixelEvent $event, OfferPixel $offerPixel)
    {
        $this->event = $event;
        $this->offerPixel = $offerPixel;
    }

    public function handle(PixelEventService $service)
    {

        try {

            // Verificar se o registro existe e possui access_token preenchido
            if ($this->offerPixel && !empty($this->offerPixel->access_token)) {
                // Chamar o serviço para enviar o pixel para o Facebook
                $service->sendToFacebookPixel($this->event, $this->offerPixel);
                Log::info('Pixel enviado para o Facebook com sucesso.', [
                    'offer_id' => $this->event->offer_id,
                ]);
                LogsPixelEvent::create([
                    'product_offering_id' => $this->event->offer_id,
                    'event_name' => $this->event->eventType,
                    'TID'=> $this->event->TID,
                    'payload' => $this->event->eventData,
                    'status' => 'Sucesso'
                ]);
            } else {
                Log::warning('Access token não encontrado ou inválido para o offer_id.', ['offer_id' => $this->event->offer_id,]);
            }
        } catch (\Exception $e) {
            // Logar o erro para análise futura
            Log::error($this->event->TID.'|Erro ao processar o envio do Facebook Pixel.', [
                'offer_id' => $this->event->offer_id,
                'error' => $e->getMessage(),
            ]);
            LogsPixelEvent::create([
                'product_offering_id' => $this->event->offer_id,
                'event_name' => $this->event->eventType,
                'TID'=> $this->event->TID,
                'payload' => $this->event->eventData,
                'status' => 'Error',
                'error' => $e,
            ]);
            throw $e;
        }
    }
}
