<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Http\Requests\Checkout\UpdatecheckoutRequest;
use App\Models\CelcashPayments;
use App\Models\Checkout;
use App\Models\CheckoutReviews;
use App\Models\OfferPixel;
use App\Models\ProductOffering;
use App\Services\CheckoutService;
use Bref\LaravelHealthCheck\Check;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


class CheckoutController extends Controller
{

    /**
     * @param $timer
     */

    protected CheckoutService $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    public function add_review(Request $request)
    {
        $validated = $request->validate([
            'checkout_id' => 'required',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'stars' => 'required|integer',
        ]);

        $user = Auth::user();

        $checkout = $this->checkoutService->handleCheckoutRequest($validated['checkout_id'], Auth::id(), 'edit');

        if (!$checkout) {
            return Responses::ERROR('Checkout não encontrado ou bloqueado!', null, 1100);
        }

        $created = CheckoutReviews::create($validated);

        return Responses::SUCCESS('Avaliação criada com sucesso', $created);
    }

    public function remove_review(Request $request)
    {
        $validated = $request->validate([
            'review_id' => 'required',
        ]);

        $user = Auth::user();

        $getReview = CheckoutReviews::where('id', $validated['review_id'])
            ->first();

        if (!$getReview) {
            return Responses::ERROR('Checkout não encontrado ou bloqueado!', null, 1100);
        }

        $checkout = $this->checkoutService->handleCheckoutRequest($getReview->checkout_id, Auth::id(), 'edit');

        if (!$checkout) {
            return Responses::ERROR('Checkout não encontrado ou bloqueado!', null, 1200);
        }

        $removed = $getReview->delete();

        return Responses::SUCCESS('Avaliação removida com sucesso!');
    }

    public function store(CheckoutRequest $request)
    {
        $getOffer = ProductOffering::where('id', $request->product_offering_id)
            ->whereHas('product', function($query) {
                $query->where('user_id', Auth::id())
                    ->where('is_deleted', 0)
                    ->where('is_blocked', 0);
            })
            ->where('is_deleted', 0)
            ->first();

        if (!$getOffer) {
            return Responses::ERROR('Oferta não encontrada ou bloqueada!', null, -1000, 404);
        }

        try {
            $createCheckout = $this->checkoutService->createCheckout($request);

            return Responses::SUCCESS("Checkout criado com sucesso", $createCheckout, 201);
        } catch (\Throwable $e) {
            Log::error('|' . $request->header('x-transaction-id') . '|Erro ao criar o checkout ' . $request->hash, ['error' => $e->getMessage()]);
            return Responses::ERROR("Não foi possível criar o checkout. Erro genérico não mapeado!", null,-1100,400);
        }
    }

    public function show(Request $request, $hashIdentifier)
    {
        try {
            $getCheckout = $this->checkoutService->getCheckoutData($hashIdentifier);
            return Responses::SUCCESS("Checkout encontrado com sucesso!", $getCheckout, 200);
        } catch (\Exception $e) {
            Log::error('|' . $request->header('x-transaction-id') . '|Erro ao buscar o Checkout ' . $request->hash, ['error' => $e->getMessage()]);

            return Responses::ERROR("Ocorreu um erro ao buscar o checkout. Erro genérico não mapeado!", null, -9999, 500);
        }
    }

    public function verify_pay($payment_id)
    {
        $getPayment = CelcashPayments::where('galax_pay_id', $payment_id)
            ->where('status', 'payed_pix')
            ->exists();

        $returnData = $getPayment ? true : false;

        return Responses::SUCCESS('', $returnData);
    }

    public function update(UpdatecheckoutRequest $request, $id)
    {
        $validated = $request->validated();
        try {
            $checkout = $this->checkoutService->handleCheckoutRequest($id, Auth::id(), 'edit');
            $updatedCheckout = $this->checkoutService->updateCheckout($checkout, $validated);
            return Responses::SUCCESS('Checkout atualizado com sucesso', $updatedCheckout);
        }catch (\Exception $e) {
                Log::error("|" . request()->header('x-transaction-id') . "| Não foi possível atualizar o checkout |", ['ERRO' => $e->getMessage()]);

                return Responses::ERROR($e->getMessage(), null, $e->getCode());
        } catch (\Throwable $th) {
            Log::error("|". request()->header('x-transaction-id').'| Não foi possível atualizar o checkout |', [ 'ERRO' => $th->getMessage()]);
            return Responses::ERROR('Não foi possível atualizar o Checkout. Erro genérico não mapeado', null, '-9999');
        }
    }

    public function destroy($id)
    {
        try {
            $checkout = $this->checkoutService->handleCheckoutRequest($id, Auth::id(), 'delete');
            $this->checkoutService->deleteCheckout($checkout);
            return Responses::success($checkout,'Checkout excluido com sucesso',200);
        } catch (\Exception $e) {
            Log::error("|" . request()->header('x-transaction-id') . "| Não foi possível excluir o checkout |", ['ERRO' => $e->getMessage()]);
            return Responses::ERROR($e->getMessage(), null, $e->getCode());

        }  catch (\Throwable $th) {
            Log::error("|". request()->header('x-transaction-id').'| Não foi possível excluir o checkout |', [ 'ERRO' => $th->getMessage()]);
            return Responses::error('O Checkout não pôde ser excluido. Erro genérico não mapeado',400, -9999);
        }
    }

    public function active_checkout($checkout_id)
    {
        $user = Auth::user();

        $getCheckout = Checkout::where('id', $checkout_id)
            ->where('is_deleted', false)
            ->whereHas('offer.product', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$getCheckout) {
            return Responses::ERROR('Checkout não localizado!', null, -1000, 404);
        }

        try {
            $updateCheckouts = Checkout::where('product_offering_id', $getCheckout->product_offering_id)
                ->update(['is_active' => 0]);

            $updateActiveCheckout = Checkout::where('id', $checkout_id)->update(['is_active' => 1]);

            return Responses::SUCCESS('Checkout ativado com sucesso!', $updateActiveCheckout, 200);
        }
        catch (\Throwable $th) {
            Log::error("|" . request()->header('x-transaction-id') . '| Não foi possível atualizar o status do checkout |', [ 'ERRO' => $th->getMessage()]);
            return Responses::error('O Checkout não pôde ser ativo. Erro genérico não mapeado',400, -9999);
        }
    }

    public function order_bumps_to_checkout($hashIdentifier)
    {
        $user = Auth::user();

        $getCheckout = Checkout::where('checkout_hash', $hashIdentifier)
            ->whereHas('offer.product', function ($query) use ($user) {
                $query->where('is_blocked', false)
                    ->where('is_deleted', false)
                    ->where('user_id', $user->id);
            })
            ->first();

        if (!$getCheckout) {
            return Responses::ERROR('Não foi possível localizar o checkout', null, -1000, 404);
        }
        $getproductOfferings = ProductOffering::whereHas('product', function($query) use ($user) {
            $query->where('user_id', $user->id)
            ->where('is_blocked', false)
            ->where('is_deleted', false);
        })->where('id', '!=', $getCheckout->product_offering_id)
            ->where('is_deleted', false)
        ->where('is_deleted', false)
            ->with(['product' => function($query) {
                $query->with('media:id,s3_url')->select('id', 'media_id', 'product_name');
            }])
        ->get(['id', 'product_id', 'offer_name', 'description', 'price', 'fake_price', 'enable_billet', 'enable_card', 'enable_pix']);

        if ($getproductOfferings->isEmpty())
            return Responses::SUCCESS("Sem ofertas para incluir como OrderBump", $getproductOfferings, 200);
        return Responses::SUCCESS("Ofertas localizadas disponíveis para inclusão como OrderBump", $this->checkoutService->formatOrderBumpData($getproductOfferings), 200);
    }

    public function get_public_checkout($checkoutHash)
    {
        try {
            $getCheckout = Checkout::with([
                'media:id,s3_name,s3_url',
                'timer:id,is_fixed,countdown,display,end_timer_title,timer_title,timer_title_color,timer_bg_color,timer_icon_color,timer_progressbar_bg_color,timer_progressbar_color',
            ])
                ->withWhereHas('offer', function ($query) {
                    $query->withWhereHas('product', function ($query) {
                        $query->where('is_blocked', false)
                            ->where('is_deleted', false)
                            ->where('is_active', true)
                            ->with('media:id,s3_url')
                            ->select('id', 'product_name', 'product_description', 'whatsapp_support', 'email_support', 'media_id');
                    })
                        ->where('is_deleted', false)
                        ->select('id', 'price', 'fake_price', 'enable_billet', 'enable_card', 'enable_pix', 'offer_type', 'charge_type', 'recurrently_installments', 'product_id');
                })
                ->with('order_bumps', function ($query) {
                    $query->withWhereHas('offer', function ($query) {
                        $query->withWhereHas('product', function ($query) {
                            $query->with('media:id,s3_url')
                                ->where('is_blocked', false)
                                ->where('is_deleted', false)
                                ->where('is_active', true)
                                ->select('id', 'product_name', 'product_description', 'media_id');
                        })
                        ->select('id', 'product_id', 'price', 'fake_price');
                    })
                    ->select('id', 'products_offerings_id', 'checkout_id');
                })
                ->with('reviews:id,checkout_id,name,description,stars')
                ->where('checkout_hash', $checkoutHash)
                ->where('is_deleted', 0)
                ->select('id', 'checkout_hash', 'checkout_title', 'order_bump_title', 'background_color', 'product_offering_id', 'banner_id', 'timer_id', 'banner_display', 'checkout_style', 'is_active_contact_and_documents_fields', 'is_active_address_fields', 'back_redirect_url', 'elements_color', 'text', 'text_display', 'text_font_color', 'text_bg_color')
                ->first();

            if (!$getCheckout) {
                return Responses::ERROR('Checkout indisponível!', null, 1100, 400);
            }

            $initiateCheckoutPixels = OfferPixel::where('product_offering_id', $getCheckout->offer->id)
                ->where('send_on_ic', true)
                ->select('pixel as pixel_id',  DB::raw("IF(access_token IS NOT NULL AND access_token != '', true, false) as token"),'send_on_generate_payment')
                ->get()
                ->toArray();

            $PixelGeneratePayment = OfferPixel::where('product_offering_id',$getCheckout->offer->id)
                ->select('pixel as pixel_id', DB::raw("IF(access_token IS NOT NULL AND access_token != '', true, false) as token"), 'send_on_generate_payment')
                ->get()
                ->toArray();

            $getCheckout->initiate_checkout_pixels = empty($initiateCheckoutPixels) ? null : $initiateCheckoutPixels;
            $getCheckout->purchase_pixels = empty($PixelGeneratePayment) ? null : $PixelGeneratePayment;

            return Responses::SUCCESS('', $getCheckout, 200);
        }
        catch (\Exception $e) {
            return Responses::ERROR('Ocorreu um erro ao buscar o checkout!!', null, -1100, 400);
        }
    }
}
