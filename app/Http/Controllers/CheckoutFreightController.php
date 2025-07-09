<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Models\CheckoutFreight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Helpers\Responses;

class CheckoutFreightController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'checkout_id' => 'required',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        $checkout = Checkout::with('offer.product')->find($validated['checkout_id']);

        if (!$checkout || $checkout->offer->product->user_id !== Auth::id()) {
            return Responses::ERROR('Checkout não encontrado', null, -1100, 404);
        }

        $freight = CheckoutFreight::create($validated);

        return Responses::SUCCESS('Frete criado com sucesso', $freight, 201);
    }

    public function destroy($freight_id)
    {
        $freight = CheckoutFreight::with('checkout.offer.product')->find($freight_id);

        if (!$freight) {
            return Responses::ERROR('Frete não encontrado', null, -1100, 404);
        }

        if ($freight->checkout->offer->product->user_id !== Auth::id()) {
            return Responses::ERROR('Este frete não pertence ao usuário autenticado', null, -1100, 400);
        }

        $freight->delete();

        return Responses::SUCCESS('Frete deletado com sucesso', null, 200);
    }
}
