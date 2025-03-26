<?php

namespace App\Listeners;

use App\Events\PixelEvent;
use App\Jobs\FacebookPixelJob;
use App\Jobs\GoogleAdsPixelJob;
use App\Models\OfferPixel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class PixelEventListener implements ShouldQueue
{
    public $shouldQueue = true; // Garante que será colocado na fila
    public function handle(PixelEvent $event)
    {
        $offerPixel = OfferPixel::where('product_offering_id', $event->offer_id)->whereNotNull('access_token')->get();

        if ($offerPixel->isNotEmpty()) {
            // Percorrer cada item na coleção de offerPixel
            foreach ($offerPixel as $pixel) {
                // Adicionar lógica baseada no pixels_id de cada registro
                switch ($pixel->pixels_id) {
                    case 1:
                        Log::info($event->TID."|PixelEventListener disparando job para fila", [
                            'offer_id' => $event->offer_id,
                            'pixel' => $pixel->pixel
                        ]);
                        // Disparar o job para cada linha individualmente
                        dispatch(new FacebookPixelJob($event, $pixel))->onQueue('FacebookPixelEvent');
                        break;
//                case 2:
//                    dispatch(new GoogleAdsPixelJob($event))->onQueue('GoogleAdsPixelEvent');
//                    break;
//                case 4:
//                    dispatch(new TikTokPixelJob($event));
//                    break;
                }
            }
        }
    }
}
