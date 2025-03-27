<?php

namespace App\Http\Controllers;

use App\Http\Requests\Product\DestroyProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\ProductCategory;
use App\Models\ProductOffering;
use App\Models\ProductType;
use App\Services\ProductService;
use App\Services\ProductUpdateException;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Helpers\Responses;
use App\Http\Requests\Product\CreateProductRequest;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Traits\Cachable;

class ProductController extends Controller
{
    use Cachable;

    public function __construct(private ProductService $productService) {}

    public function get_product_categories()
    {
        return Responses::SUCCESS('', ProductCategory::select(['id','name'])->get());
    }

    public function get_product_types()
    {
        return Responses::SUCCESS('', ProductType::select(['id','name'])->get());
    }

    public function store(CreateProductRequest $request)
    {
        $user = Auth::user();

        $verifyIfUserHasPayPendences = UserService::getPayConfigsPendences();

        $is_active = $verifyIfUserHasPayPendences ? 0 : 1;

        try {
            $product = Product::create([
                'product_name' => $request->product_name,
                'product_description' => $request->product_description,
                'email_support' => $request->email_support,
                'whatsapp_support' => null,
                'card_description' => 'ASTRAPAY*COMPRA',
                'refund_time' => 7,
                'media_id' => $request->media_id,
                'user_id' => $user->id,
                'product_types_id' => $request->product_type,
                'product_categories_id' => $request->product_category,
                'is_blocked' => 0,
                'is_active' => $is_active,
                'is_deleted' => 0
            ]);
            $this->removeCache($request->header('x-transaction-id'), 'user_' . Auth::id(). '_getProducts_');
            return Responses::SUCCESS('Produto criado com sucesso',$product, 201);
        } catch (\Throwable $th) {
            Log::error('Não foi possível criar o produto', ['error' => $th->getMessage()]);
            return Responses::ERROR('Ocorreu um erro ao tentar criar o produto', null, '-9999', 400);
        }
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $itemsPerPage = $request->query('items_per_page', 10);

        try {
            /*$products =  $this->getOrSetCache($request->header('x-transaction-id'),'user_' . $user->id . '_getProducts_', function () use ($user, $itemsPerPage) {
                return Product::select('id', 'product_name', 'is_active', 'is_blocked','media_id', 'product_description', 'product_types_id', 'product_categories_id', 'refund_time', 'card_description' , 'email_support', 'whatsapp_support', 'created_at' )
                    ->with('media:id,s3_url','product_category:id,name','product_type:id,name')
                    ->where('user_id', $user->id)
                    ->where('is_deleted', 0)
                    ->orderByDesc('id')
                    ->paginate($itemsPerPage);
                }, 600);*/ // Cache por 10 minutos (600 segundos)

            $products = Product::select('id', 'product_name', 'is_active', 'is_blocked','media_id', 'product_description', 'product_types_id', 'product_categories_id', 'refund_time', 'card_description' , 'email_support', 'whatsapp_support', 'created_at' )
                ->with('media:id,s3_url','product_category:id,name','product_type:id,name')
                ->where('user_id', $user->id)
                ->where('is_deleted', 0)
                ->orderByDesc('id')
                ->paginate($itemsPerPage);

            return Responses::SUCCESS('', $products);
        } catch (\Throwable $th) {
            Log::error('Não foi possível listar os produtos', ['error' => $th->getMessage()]);

            return Responses::ERROR('Não foi possível recuperar a lista de produtos. Erro genérico não mapeado', null,'-9999',400);
        }
    }

    public function get_offers(Request $request, $product_id)
    {
        $user = Auth::user();
        $itemsPerPage = $request->query('items_per_page', 10);

        try {
            $offers = $this->getOrSetCache($request->header('x-transaction-id'),'user_' . $user->id . '_getOffersByProduct_'.$product_id, function () use ($user,$product_id, $itemsPerPage) {
                return ProductOffering::whereHas('product', function ($query) use ($user, $product_id) {
                $query->where('user_id', $user->id)
                    ->where('id', $product_id)
                    ->where('is_deleted', 0);
            })
                ->where('is_deleted', 0)
                ->with(['checkouts' => function ($query) {
                    $query->where('is_active', 1)
                        ->orderBy('id', 'desc')
                        ->select('id', 'checkout_hash as active_checkout', 'product_offering_id');
                }])
                    ->with(['offerPixels' => function($query) {
                        $query->where('status', 1);
                    }])
                ->orderByDesc('id')
                ->paginate($itemsPerPage);
        }, 600); // Cache por 10 minutos (600 segundos)

            $formattedOffers = $offers->through(function ($offer) {
                return $this->formatOffersWithPixels($offer);
            });
            return Responses::SUCCESS('', $formattedOffers);
        }
        catch (\Throwable $th) {
            Log::error('Não foi possível listar as ofertas desse produto', ['error' => $th->getMessage()]);

            return Responses::ERROR('Não foi possível listar as ofertas desse produto. Erro genérico não mapeado', null,'-9999',400);
        }

    }

    protected function formatOffersWithPixels($offers)
    {
        if (!$offers) {
            return null;
        }

        $formattedOffer = $offers->toArray();
        $formattedOffer['integration_faceboook_pixel'] = [];

        if ($offers->offerPixels->isNotEmpty()) {
            foreach ($offers->offerPixels as $offerPixel) {
                if ($offerPixel->pixels_id == 1) {
                    $formattedOffer['integration_faceboook_pixel'][] = [
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


    public function update(UpdateProductRequest $request){

        if ($request->is_active) {
            $verifyIfUserHasPayPendences = UserService::getPayConfigsPendences();

            if ($verifyIfUserHasPayPendences)
                return Responses::ERROR('O produto não pôde ser atualizado, pois o usuário não finalizou as configurações da sua conta!', null, 1100, 400);
        }

        try {
            $product = $request->validatedProduct();
            $updatedProduct = $this->productService->updateProduct($product, $request->validated());
            $this->removeCache($request->header('x-transaction-id'), 'user_' . Auth::id(). '_getProducts_');
            return Responses::SUCCESS('Produto atualizado com sucesso', $updatedProduct);

        } catch (ProductUpdateException $e) {
            return Responses::ERROR($e->getMessage(), null, $e->getCode(), 400);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar o produto', ['error' => $e->getMessage()]);
            return Responses::ERROR('Não foi possível atualizar o produto. Erro genérico não mapeado', null, '-9999');
        }
    }

    public function destroy(DestroyProductRequest $destroy)
    {
        try{
            $getProduct = $destroy->validatedProduct();
            $product = $this->productService->softDeleteProduct($getProduct);
            $this->removeCache($destroy->header('x-transaction-id'), 'user_' . Auth::id(). '_getProducts_');
            return Responses::SUCCESS('Produto deletado com sucesso!', [ "id" => $product->id ]);
        }
        catch (\Throwable $th) {
            Log::error('Não foi possível deletar o produto', ['error' => $th->getMessage()]);
            return Responses::ERROR('Não foi possível deletar o produto. Erro genérico não mapeado', null, '-9999');
        }
    }

    public function duplicate(UpdateProductRequest $request)
    {
        try {
            $getProduct = $request->validatedProduct();
            $duplicatedProduct = $getProduct->replicate();
            $duplicatedProduct->save();

            return Responses::SUCCESS('Produto duplicado com sucesso', $duplicatedProduct);
        }
        catch (\Throwable $th) {
            Log::error('Não foi possível duplicar o produto', ['error' => $th->getMessage()]);

            return Responses::ERROR('Não foi possível duplicar o produto. Erro genérico não mapeado', null, '-9999');
        }
    }
}
