<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Http\Requests\Orderbump\OrderBumpRequest;
use App\Models\Checkout;
use App\Models\OrderBump;
use App\Models\ProductOffering;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderBumpController extends Controller
{
    public function store(OrderBumpRequest $request)
    {
        $user = Auth::user();

        $getCheckout = Checkout::where('id', $request->checkout_id)
            ->whereHas('offer.product', function ($query) use ($user) {
                $query->where('is_blocked', false)
                    ->where('is_deleted', false)
                    ->where('user_id', $user->id);
            })
            ->first();

        if (!$getCheckout) {
            return Responses::ERROR('Checkout não encontrado!', null, -1000, 404);
        }

        foreach ($request->products_offerings as $index => $offer) {
            $isActiveOrderBump = OrderBump::where('checkout_id', $request->checkout_id)->where('products_offerings_id', $offer['products_offerings_id'])->exists();

            if ($isActiveOrderBump) {
                continue;
            }

            $belongsToUser = ProductOffering::where('id', $offer['products_offerings_id'])
                ->whereHas('product', function ($query) use ($user) {
                    $query->where('is_blocked', false)
                    ->where('is_deleted', false)
                    ->where('user_id', $user->id);
                })
                ->exists();

            if (!$belongsToUser) {
                continue;
            }

            $getLastPosition = OrderBump::select('position')->where('checkout_id', $request->checkout_id)->orderBy('position', 'desc')->first();

            $position = 0;

            if ($getLastPosition) {
                $position = $getLastPosition->position + 1;
            }

            try {
                $createdOrderBump = OrderBump::create([
                    'checkout_id' => $request->checkout_id,
                    'products_offerings_id' => $offer['products_offerings_id'],
                    'position' => $position,
                ]);
            }
            catch (\Throwable $th) {
                Log::error('Ocorreu um erro ao criar o order bump', ['error' => $th->getMessage()]);
                return Responses::ERROR('Ocorreu um erro ao criar o order bump', null, -9999, 500);
            }
        }

        return Responses::SUCCESS('Order bumps adicionados com sucesso!', [ 'id' => $createdOrderBump->id], 201);
    }

    public function destroy($id)
    {
        $user = Auth::user();

        $getOrderBump = OrderBump::select('id')
            ->where('id', $id)
            ->whereHas('offer.product', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->first();

        if (!$getOrderBump) {
            return Responses::ERROR('Order bump não localizado!', null, -1000, 404);
        }

        try {
            $getOrderBump->delete();

            return Responses::SUCCESS('Order bump deletado com sucesso!', null, 200);
        }
        catch (\Throwable $th) {
            Log::error('Ocorreu um erro ao deletar o order bump', ['error' => $th->getMessage()]);

            return Responses::ERROR('Ocorreu um erro ao deletar o order bump!', null, -1100, 400);
        }
    }
}
