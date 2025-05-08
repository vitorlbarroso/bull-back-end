<?php

namespace App\Services;
use App\Http\Helpers\Responses;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Models\Checkout;
use App\Models\OfferPixel;
use App\Models\Timer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class CheckoutService
{

    protected $timer_service;

    public function __construct(TimerService $timerService)
    {
        $this->timer_service = $timerService;
    }


    public function createCheckout(CheckoutRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create Timer
            $timer = $this->timer_service->createTimer();

            // Create Checkout
            $checkout = $this->storeCheckout($request, $timer->id);
            DB::commit();

            return $checkout;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('|'. $request->header('x-transaction-id').'|Erro ao Criar o Checkout', ['error' => $e->getMessage()]);
            Log::warning('|'. $request->header('x-transaction-id').'|Realizando o Rollback dessa transação');

            throw $e;
        }
    }

    public function storeCheckout($request, $timerId)
    {
        $data = [
            'product_offering_id' => $request->product_offering_id,
            'is_active' => $request->is_active ?? true,
            'is_deleted' => $request->is_deleted ?? false,
            'background_color' => $request->background_color ?? '#ffffff',
            'checkout_title' => $request->checkout_title ?? 'Novo Checkout',
            'exit_popup' => $request->exit_popup ?? false,
            'order_bump_title' => $request->order_bump_title ?? 'Adquira ',
            'whatsapp_is_active' => $request->whatsapp_is_active ?? false,
            'whatsapp_message' => $request->whatsapp_message ?? null,
            'whatsapp_number' => $request->whatsapp_number ?? null,
            'checkout_hash' => \Str::random(10),
            'timer_id' => $timerId,
        ];
        return Checkout::create($data);
    }

    public function getCheckoutData($hashIdentifier)
    {
        try {

            $checkout = Checkout::with([
                'media:id,s3_name,s3_url',
                'timer:id,is_fixed,countdown,display,end_timer_title,timer_title,timer_title_color,timer_bg_color,timer_icon_color,timer_progressbar_bg_color,timer_progressbar_color',
                'order_bumps.offer:id,offer_name,fake_price,price,product_id',
                'order_bumps.offer.product.media:id,s3_url',
                'reviews:id,checkout_id,name,description,stars'
            ])
                ->where('checkout_hash', $hashIdentifier)
                ->where('is_deleted', 0)
                ->select('id', 'checkout_hash', 'checkout_title', 'order_bump_title', 'background_color', 'product_offering_id', 'banner_id', 'timer_id', 'banner_display', 'checkout_style', 'is_active_contact_and_documents_fields', 'is_active_address_fields', 'back_redirect_url', 'elements_color', 'text', 'text_display', 'text_font_color', 'text_bg_color', 'fixed_values_fields')
                ->first();
            return $this->formatCheckoutData($checkout);
        } catch (\Exception $e) {
            Log::error('|' . request()->header('x-transaction-id'). '|Erro ao Consultar o Checkout ' . $hashIdentifier, ['error' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }
    protected function formatCheckoutData($checkout)
    {

        $initiateCheckoutPixels = OfferPixel::where('product_offering_id', $checkout->offer->id)
            ->where('send_on_ic', true)
            ->select('pixel as pixel_id',  DB::raw("IF(access_token IS NOT NULL AND access_token != '', true, false) as token"))
            ->get()
            ->toArray();

        $PixelGeneratePayment = OfferPixel::where('product_offering_id', $checkout->offer->id)
            ->where('send_on_generate_payment', true)
            ->select('pixel as pixel_id',  DB::raw("IF(access_token IS NOT NULL AND access_token != '', true, false) as token"))
            ->get()
            ->toArray();

        return [
            'checkout_infos' => [
                'id' => $checkout->id,
                'checkout_hash' => $checkout->checkout_hash,
                'checkout_title' => $checkout->checkout_title,
                'order_bump_title' => $checkout->order_bump_title,
                'exit_popup' => $checkout->exit_popup,
                'checkout_style' => $checkout->checkout_style,
                'is_active_contact_and_documents_fields' => $checkout->is_active_contact_and_documents_fields,
                'is_active_address_fields' => $checkout->is_active_address_fields,
                'fixed_values_fields' => $checkout->fixed_values_fields,
            ],
            'initiate_checkout_pixels' => empty($initiateCheckoutPixels) ? null : $initiateCheckoutPixels,
            'purchase_pixels' => empty($PixelGeneratePayment) ? null : $PixelGeneratePayment,
            'offer_data' => [
                'id' => $checkout->offer->id,
                'offer_name' => $checkout->offer->offer_name,
                'fake_price' => $checkout->offer->fake_price,
                'price' => $checkout->offer->price,
            ],
            'order_bumps' => $checkout->order_bumps->map(function($orderBump)  {
                return [
                    'id' => $orderBump->id,
                    'product_offering_id' => $orderBump->products_offerings_id,
                    'checkout_id' => $orderBump->checkout_id,
                    'offer_name' => $orderBump->offer->offer_name,
                    'fake_price' => $orderBump->offer->fake_price,
                    'price' => $orderBump->offer->price,
                    's3_url' => $orderBump->offer->product->media->s3_url ?? null
                ];
            }),
            'timer' => [
                'id' => $checkout->timer->id,
                'is_fixed' => $checkout->timer->is_fixed,
                'countdown' => $checkout->timer->countdown,
                'display' => $checkout->timer->display,
                'end_timer_title' => $checkout->timer->end_timer_title,
                'timer_title' => $checkout->timer->timer_title,
                'timer_title_color' => $checkout->timer->timer_title_color,
                'timer_bg_color' => $checkout->timer->timer_bg_color,
                'timer_icon_color' => $checkout->timer->timer_icon_color,
                'timer_progressbar_bg_color' => $checkout->timer->timer_progressbar_bg_color,
                'timer_progressbar_color' => $checkout->timer->timer_progressbar_color,
            ],
            'banner' => [
                'id' => $media = $checkout->media->id ?? null,
                's3_name' => $checkout->media->s3_name ?? null,
                's3_url' => $checkout->media->s3_url ?? null,
                'display' => $checkout->banner_display ?? true
            ],
            'configs' => [
                'background_color' => $checkout->background_color,
                'elements_color' => $checkout->elements_color,
                'back_redirect_url' => $checkout->back_redirect_url,
            ],
            'text' => [
                'text' => $checkout->text,
                'text_font_color' => $checkout->text_font_color,
                'text_bg_color' => $checkout->text_bg_color,
                'text_display' => $checkout->text_display,
            ],
            'reviews' => $checkout->reviews->map(function($review)  {
                return [
                    'id' => $review->id,
                    'name' => $review->name,
                    'description' => $review->description,
                    'stars' => $review->stars,
                ];
            }),
        ];
    }

    public function handleCheckoutRequest($id, $userId, $action)
    {
        $checkout = $this->findCheckoutForUser($id, $userId);

        if ($checkout === null) {
            throw new \Exception('Checkout não localizado', '-1000');
        }

        if ($action === 'edit' && !$this->canBeEdited($checkout)) {
            throw new \Exception('Não é possível editar Checkout desativado ou deletado', '-1100');
        }

        if ($action === 'delete' && !$this->canBeDeleted($checkout)) {
            throw new \Exception('Checkout não pode ser excluído', '-1999');
        }

        return $checkout;
    }

    public function findCheckoutForUser($id, $userId)
    {
        return Checkout::where('id', $id)
            ->whereHas('offer.product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['offer.product'])
            ->first();
    }

    public function canBeEdited($checkout)
    {
        return $checkout->is_active && !$checkout->is_deleted;
    }

    public function updateCheckout($checkout, $data)
    {
        $checkoutUpdate = $this->checkoutUpdateFormat($data);
        $checkout->update($checkoutUpdate);
        $this->timer_service->updateTimerService($checkout->timer_id, $data);
        $checkout->save();
        return $checkout;
    }

    public function canBeDeleted($checkout)
    {
        return $checkout && $checkout->isDeleted != 1;
    }

    public function deleteCheckout($checkout)
    {
        $checkout->is_deleted = 1;
        $checkout->deleted_at = now();
        $checkout->save();
    }

    public function checkoutUpdateFormat($validatedData)
    {

        return [
            'checkout_title' => $validatedData['checkout_title'],
            'product_offering_id' => $validatedData['product_offering_id'],
            'exit_popup' => $validatedData['exit_popup'] ?? false,
            'order_bump_title' => $validatedData['order_bump_title'],
            'banner_id' => $validatedData['banner']['id'] ?? null,
            'banner_display' => $validatedData['banner']['display'] ?? true,
            'background_color' => $validatedData['configs']['background_color'],
            'checkout_style' => $validatedData['checkout_style'],
            'is_active_contact_and_documents_fields' => $validatedData['is_active_contact_and_documents_fields'],
            'is_active_address_fields' => $validatedData['is_active_address_fields'],
            'fixed_values_fields' => $validatedData['fixed_values_fields'],
            'back_redirect_url' => $validatedData['configs']['back_redirect_url'] ?? null,
            'elements_color' => $validatedData['configs']['elements_color'],
            'text' => $validatedData['text']['text'],
            'text_display' => $validatedData['text']['text_display'],
            'text_font_color' => $validatedData['text']['text_font_color'],
            'text_bg_color' => $validatedData['text']['text_bg_color'],
            // Add other fields as necessary
        ];

    }

    public function formatOrderBumpData($checkout)
    {
        $results = [];

        foreach ($checkout as $check) {
            // Verifica se o produto já está presente na lista de resultados
            if (!isset($results[$check->product_id])) {
                if ($check->product->media) {
                    $s3_url = $check->product->media->s3_url;
                } else {
                    $s3_url = null;
                }

                $results[$check->product_id] = [
                    'product_id' => $check->product_id,
                    'product_name' => $check->product->product_name,
                    's3_url' => $s3_url,
                    'offers' => []
                ];
            }

            // Adiciona a oferta ao produto
            $results[$check->product_id]['offers'][] = [
                'id' => $check->id,
                'offer_name' => $check->offer_name,
                'description' => $check->description,
                'price' => $check->price,
                'fake_price' => $check->fake_price,
            ];
        }

// Reindexa a lista para a estrutura desejada
        $results = array_values($results);
        return $results;
    }

}
