<?php

namespace App\Services;

use App\Events\PixelEvent;
use App\Models\OfferPixel;
use Illuminate\Support\Facades\Http;

class PixelEventService
{
    /**
     * Envia o evento para o Google Ads.
     */
    public function sendToGoogleAds(array $eventData)
    {
        $response = Http::post('https://www.google-ads.com/event', $eventData);
        if ($response->failed()) {
            throw new \Exception('Google Ads event failed: ' . $response->body());
        }
    }

    /**
     * Envia o evento para o Google Analytics.
     */
    public function sendToGoogleAnalytics(array $eventData)
    {
        $response = Http::post('https://www.google-analytics.com/event', $eventData);
        if ($response->failed()) {
            throw new \Exception('Google Analytics event failed: ' . $response->body());
        }
    }

    /**
     * Envia o evento para o Facebook Pixel.
     */
    public function sendToFacebookPixel(PixelEvent $event, $offer_pixel)
    {
        $API_VERSION = '21.0';
        $eventData = $event->eventData;
        $ACCESS_TOKEN = $offer_pixel->access_token;
        $PIXEL_ID = $offer_pixel->pixels_id;
        $response = Http::post('https://graph.facebook.com/'.$API_VERSION.'/'.$PIXEL_ID.'/events?access_token='.$ACCESS_TOKEN, $eventData);
        if ($response->failed()) {
            throw new \Exception('Facebook Pixel event failed: ' . $response->body());
        }
    }

    /**
     * Envia o evento para o TikTok Pixel.
     */
    public function sendToTikTokPixel(array $eventData)
    {
        $response = Http::post('https://business-api.tiktok.com/events', $eventData);
        if ($response->failed()) {
            throw new \Exception('TikTok Pixel event failed: ' . $response->body());
        }
    }

    public function storePixel($data)
    {
       return  OfferPixel::create([
            'pixels_id' => $data->pixels_id,
            'product_offering_id' => $data->product_offering_id,
            'access_token' => $data->access_token ?? null,
            'status' => true, // Set status to true
        ]);
    }

   public function FormatDataPixel(array $data): array
    {
        // Mapeia as transformações necessárias para cada campo
        $transformations = [
            'em' => 'lowercase_hash256',
            'ph' => 'hash256',
            'fn' => 'lowercase_hash256',
            'ln' => 'lowercase_hash256',
            'ge' => 'lowercase_hash256',
            'country' => 'lowercase_hash256',
            'zp' => 'lowercase_hash256',
            'st' => 'lowercase_hash256',
            'db' => 'hash256',
        ];

        // Aplica as transformações especificadas no mapeamento
        foreach ($transformations as $key => $transformation) {
            if (isset($data['user_data'][$key]) && is_array($data['user_data'][$key])) {
                $data['user_data'][$key] = array_map(
                    function ($value) use ($transformation) {
                        return self::applyTransformation($value, $transformation);
                    },
                    $data['user_data'][$key]
                );
            }
        }

        return $data;
    }

// Função genérica que aplica a transformação especificada
   public function applyTransformation(string $value, string $transformation): string
    {
        switch ($transformation) {
            case 'hash256':
                return hash('sha256', $value);
            case 'lowercase_hash256':
                return hash('sha256', strtolower($value));
            default:
                return $value;
        }
    }
}
