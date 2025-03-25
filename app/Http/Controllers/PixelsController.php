<?php

namespace App\Http\Controllers;

use App\Events\PixelEvent;
use App\Http\Helpers\Responses;
use App\Http\Requests\Pixels\PixelRequest;
use App\Http\Requests\Pixels\PixelSendRequest;
use App\Models\Pixels;
use App\Services\PixelEventService;
use Illuminate\Http\Request;

class PixelsController extends Controller
{

    public function index()
    {
        return Pixels::all();
    }

    public function store(PixelRequest $request)
    {
        $request = $request->validated();
        try {
            PixelEventService::storePixel($request);
        } catch (\Exception $e) {
            return Responses::ERROR('Não foi possível salvar o pixel', $e->getMessage(), -1000);
        }
    }

    public function send(PixelSendRequest $request, $offer_id)
    {
        $request= $request->validated();
         $data = PixelEventService::FormatDataPixel($request);
        \Log::info($request()->header('x-transaction-id').'| Disparando envio de Pixel via event|', $data);
        event(new PixelEvent($offer_id, $request->event_name, $data, $request()->header('x-transaction-id')));
    }
    public function show(Pixels $pixels)
    {
        return $pixels;
    }

    public function update(Request $request, pixels $pixels)
    {
        $data = $request->validate([
            'name' => ['required'],
            'platform' => ['required'],
        ]);

        $pixels->update($data);

        return $pixels;
    }

    public function destroy(pixels $pixels)
    {
        $pixels->delete();

        return response()->json();
    }
}
