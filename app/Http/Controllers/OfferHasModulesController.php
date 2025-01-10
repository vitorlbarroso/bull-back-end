<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfferHasModulesRequest;
use App\Models\OfferHasModules;

class OfferHasModulesController extends Controller
{
    public function index()
    {
        return OfferHasModules::all();
    }

    public function store(OfferHasModulesRequest $request)
    {
        return OfferHasModules::create($request->validated());
    }

    public function show(OfferHasModules $offerHasModules)
    {
        return $offerHasModules;
    }

    public function update(OfferHasModulesRequest $request, OfferHasModules $offerHasModules)
    {
        $offerHasModules->update($request->validated());

        return $offerHasModules;
    }

    public function destroy(OfferHasModules $offerHasModules)
    {
        $offerHasModules->delete();

        return response()->json();
    }
}
