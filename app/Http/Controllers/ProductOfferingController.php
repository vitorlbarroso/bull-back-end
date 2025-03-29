<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Http\Requests\Offering\CreateProductOfferingRequest;
use App\Http\Requests\Offering\UpdateProductOfferingRequest;
use App\Http\Requests\Pixels\PixelRequest;
use App\Models\Checkout;
use App\Models\OfferPixel;
use App\Models\Product;
use App\Models\ProductOffering;
use App\Services\CheckoutService;
use App\Services\PixelEventService;
use App\Traits\Cachable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProductOfferingController extends Controller
{
    use Cachable;
    protected CheckoutService $checkoutService;
    protected PixelEventService $pixelservice;

    public function __construct(CheckoutService $checkoutService, PixelEventService $pixel)
    {
        $this->checkoutService = $checkoutService;
        $this->pixelservice = $pixel;
    }

    public function offer_checkouts($offer_id)
    {
        $user = Auth::user();

        $getCheckouts = Checkout::where('product_offering_id', $offer_id)
            ->where('is_deleted', false)
            ->whereHas('offer.product', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get(['id', 'checkout_title', 'checkout_hash', 'is_active', 'created_at']);

        return Responses::SUCCESS('', $getCheckouts);
    }

    public function index(Request $request)
    {
        $itemsPerPage = $request->query('items_per_page', 10);

        $getAllProductOfferings = $this->getOrSetCache($request->header('x-transaction-id'),'user_' . Auth::id(). '_getOffers_', function () use ($itemsPerPage) {
             return ProductOffering::where('is_deleted', 0)
                ->whereHas('product', function($query) {
                    $query->where('user_id', Auth::id())
                        ->where('is_deleted', 0);
                })
                ->with(['product', 'checkouts' => function($query) {
                    $query->where('is_active', 1)
                        ->orderBy('id', 'desc');
                }
                ])
                 ->with(['offerPixels' => function($query) {
                     $query->where('status', 1);
                 }])
                ->orderBy('id', 'desc')
                ->paginate($itemsPerPage);
         }, 600); // Cache por 10 minutos (600 segundos)
        $formattedOffers = $getAllProductOfferings->through(function ($offer) {
            return $this->formatOffersWithPixels($offer);
        });

        return Responses::SUCCESS('', $formattedOffers);
    }

    public function store(CreateProductOfferingRequest $request)
    {
        $user = Auth::user();

        $getProduct = Product::where('id', $request->product_id)
            ->where('user_id', $user->id)
            ->where('is_blocked', 0)
            ->where('is_deleted', 0)
            ->exists();

        if (!$getProduct) {
            return Responses::ERROR('Produto bloqueado ou não localizado', null, -1100, 400);
        }

        $createOffer = null;
        try {
            DB::transaction(function () use ($user, $request, &$createOffer) {
                $createOffer = ProductOffering::create([
                    'user_id'=> $user->id,
                    'product_id'=> $request->product_id,
                    'utmify_token'=> $request->utmify_token || null,
                    'offer_name'=> $request->offer_name,
                    'description'=> $request->offer_name,
                    'price'=> $request->price,
                    'fake_price'=> $request->fake_price,
                    'offer_type'=> $request->offer_type,
                    'charge_type'=> $request->charge_type,
                    'recurrently_instalments'=> $request->recurrently_installments,
                    'enable_billet'=> $request->enable_billet,
                    'enable_card'=> $request->enable_card,
                    'enable_pix'=> $request->enable_pix,
                    'sale_page_completed_url'=> $request->sale_page_completed_url,
                ]);
                $id_oferta['product_offering_id' ] = $createOffer->id;
                $checkoutRequest = new CheckoutRequest($id_oferta);
                $createCheckout = $this->checkoutService->createCheckout($checkoutRequest);
                $this->PixelCreate($request, $createOffer->id);

            });

            $activeCheckout = $createOffer->checkouts()->where('is_active', true)->latest()->value('checkout_hash');

            $responseData = [
                'offer' => $createOffer,
                'active_checkout' => $activeCheckout,
                'integration_facebook' =>$request->input('integration_facebook')
            ];
            $this->removeCache($request->header('x-transaction-id'), 'user_' . Auth::id(). '_getOffers_');
            $this->removeCache($request->header('x-transaction-id'), 'user_' . Auth::id(). '_getOffersByProduct_' .$request->product_id);
            return Responses::SUCCESS("Oferta criada com sucesso", $responseData, 201);
        }catch (\Throwable $th) {
            Log::error('Não foi possível criar a oferta', ['error' => $th->getMessage()]);
            return Responses::ERROR('Não foi possível criar a oferta. Erro genérico não mapeado', null, '-9999', 400);
        }
    }

    public function PixelCreate($request, $offerid)
    {
        $integrationFacebookData = $request->input('integration_facebook', []);

        if (is_array($integrationFacebookData)) {
            foreach ($integrationFacebookData as $pixelData) {
                $pixel = (object)[
                    'pixel_id' => $pixelData['pixel_id'],
                    'product_offering_id' =>$offerid,
                    'access_token' => $pixelData['token'] ?? null,
                    'send_on_ic' => $pixelData['send_initiate_checkout'] ?? true, // Define um valor padrão caso não esteja presente
                    'send_on_generate_payment' => $pixelData['send_purchase_on_generate_payment'] ?? false, // Define um valor padrão caso não esteja presente
                ];
                $this->pixelservice::storePixel($pixel); // Cast para objeto para manter a assinatura da função
            }
        }
    }

    public function show(Request $request, $id)
    {

        $getOffer = $this->getOrSetCache($request->header('x-transaction-id'),'user_' . Auth::id(). '_getOffersById_' .$id, function () use ($id) {
                    return ProductOffering::where('id', $id)
                    ->whereHas('product', function($query) {
                        $query->where('user_id', Auth::id())
                            ->where('is_deleted', 0);
                    })
                    ->with(['product', 'checkouts' => function($query) {
                        $query->where('is_active', 1)
                            ->orderBy('id', 'desc');
                    }])
                        ->with(['offerPixels' => function($query) {
                            $query->where('status', 1);
                        }])
                    ->where('is_deleted', 0)
                    ->first();
        }, 600); // Cache por 10 minutos (600 segundos)
        if (!$getOffer) {
            return Responses::ERROR('Oferta não localizada', null, -1100, 404);
        }
        $formattedOffers = $getOffer->through(function ($offer) {
            return $this->formatOffersWithPixels($offer);
        });

        return Responses::SUCCESS('Oferta encontrada com sucesso', $formattedOffers);
    }

    protected function formatOffersWithPixels($offers)
    {
        if (!$offers) {
            return null;
        }

        $formattedOffer = $offers->toArray();
        $formattedOffer['integration_facebook_pixel'] = [];

        if ($offers->offerPixels->isNotEmpty()) {
            foreach ($offers->offerPixels as $offerPixel) {
                if ($offerPixel->pixels_id == 1) {
                    $formattedOffer['integration_facebook_pixel'][] = [
                        'id' => $offerPixel->id,
                        'pixel_id' => $offerPixel->pixel,
                        'access_token' => $offerPixel->access_token,
                        'send_initiate_checkout' => (bool) $offerPixel->send_on_ic,
                        'send_purchase_on_generate_payment' => (bool) $offerPixel->send_on_generate_payment,
                    ];
                }
            }
        }
        unset($formattedOffer['offer_pixels']);
        return $formattedOffer;

    }
    public function update(UpdateProductOfferingRequest $request, $id)
    {
        $validated = $request->validated();

        $getOffer = ProductOffering::where('id', $id)
            ->whereHas('product', function($query) {
                $query->where('user_id', Auth::id())
                    ->where('is_deleted', 0);
            })
            ->with('product')
            ->where('is_deleted', 0)
            ->first();

        if (!$getOffer) {
            return Responses::ERROR('Oferta não localizada', null, -1100, 404);
        }

        if ($request->price && $request->price < 9)
            return Responses::ERROR('O preço deve ser maior que 9 reais', null, -1200, 400);

        try {
             $product_id = $getOffer->product_id;
             $checkouts = Checkout::where('product_offering_id', $getOffer->id)->get();
             $getOffer->update($validated);
             $getOffer->save();
             $validated['id'] = $id;
            $checkoutData = $checkouts->map(function ($checkout) {
                return [
                    'active_checkout' => $checkout->checkout_hash,
                ];
            })->toArray();

            // Remove os pixels do Facebook, se houver
            if ($request->has('removed_facebook_pixel') && is_array($request->removed_facebook_pixel)) {
                $idsToRemove = collect($request->removed_facebook_pixel)
                    ->pluck('id')
                    ->toArray();

                Log::info('Analisando dados para remocao do Pixel', ['Pixel Id' => $idsToRemove]);

                OfferPixel::whereIn('id', $idsToRemove)
                    ->whereHas('productOffering', function ($poQuery) {
                        $poQuery->whereHas('product', function ($productQuery) {
                            $productQuery->where('user_id', Auth::id());
                        });
                    })
                    ->delete();
            }
             $this->PixelCreate($request, $id);

            $validated['checkouts'] = $checkoutData;
            $this->removeCache($request->header('x-transaction-id'), 'user_' . Auth::id(). '_getOffersById_' .$id);
            $this->removeCache($request->header('x-transaction-id'), 'user_' . Auth::id(). '_getOffers_');
            $this->removeCache($request->header('x-transaction-id'), 'user_' . Auth::id(). '_getOffersByProduct_' .$product_id);
            return Responses::SUCCESS('Oferta atualizada com sucesso!', $validated);
        }
        catch (\Throwable $th) {
            Log::warning('Não foi possível atualizar a oferta', ['error' => $th->getMessage()]);

            return Responses::ERROR('Não foi possível atualizar a oferta. Erro genérico não mapeado', null, '-9999', 400);
        }
    }

    public function destroy(Request $request, $id)
    {
        $getOffer = ProductOffering::where('id', $id)
            ->whereHas('product', function($query) {
                $query->where('user_id', Auth::id())
                    ->where('is_deleted', 0);
            })
            ->with('product')
            ->where('is_deleted', 0)
            ->first();

        if (!$getOffer)
            return Responses::ERROR('Oferta não localizada', null, -1100, 404);

        try {
            $getOffer->update([
                'is_deleted' => 1
            ]);
            $getOffer->save();
            $this->removeCache($request->header('x-transaction-id'), 'user_' . Auth::id(). '_getOffersById_'.$id);
            $this->removeCache($request->header('x-transaction-id'), 'user_' . Auth::id(). '_getOffers_');

            return Responses::SUCCESS('Oferta removida com sucesso!', $getOffer->id);
        }
        catch (\Throwable $th) {
            Log::warning('Não foi possível deletar a oferta', ['error' => $th->getMessage()]);

            return Responses::ERROR('Não foi possível deletar a oferta. Erro genérico não mapeado', null, '-9999', 400);
        }
    }

    public function AllOffers(Request $request)
    {
        $itemsPerPage = $request->query('items_per_page', 10);

        $getAllProductOfferings =
            $this->getOrSetCache($request->header('x-transaction-id'),'user_' . Auth::id(). '_getOffers_', function () use ($itemsPerPage) {
                return ProductOffering::where('is_deleted', 0)
                    ->whereHas('product', function($query) {
                        $query->where('user_id', Auth::id())
                            ->where('is_deleted', 0);
                    })
                    ->with(['product', 'checkouts' => function($query) {
                        $query->where('is_active', 1)
                            ->orderBy('id', 'desc');
                    }])
                    ->orderBy('id', 'desc')
                    ->paginate($itemsPerPage);
            }, 600); // Cache por 10 minutos (600 segundos)
        Log::info('Listando todas as ofertas ', ['Ofertas' => $getAllProductOfferings]);
        return Responses::SUCCESS('', $getAllProductOfferings);
    }
}
