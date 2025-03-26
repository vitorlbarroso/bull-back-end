<?php

namespace App\Http\Controllers;

use App\Events\PixelEvent;
use App\Http\Helpers\Responses;
use App\Http\Requests\Pixels\PixelRequest;
use App\Http\Requests\Pixels\PixelSendRequest;
use App\Models\Pixels;
use App\Services\PixelEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PixelsController extends Controller
{

    public function index()
    {
        return Pixels::all();
    }

    public function store(PixelRequest $request)
    {
        try {
            $data= PixelEventService::storePixel($request);
            return Responses::SUCCESS(' Pixel inserido com sucesso',$data,201);
        } catch (\Exception $e) {
            Log::error($request->header('x-transaction-id').'| Não foi possível salvar o pixel|'.$e->getMessage());
            return Responses::ERROR('Não foi possível salvar o pixel', null,-1000);
        }
    }

    public function send(PixelSendRequest $request)
    {
        try {
            $data = PixelEventService::FormatDataPixel($request->validated());
            Log::info($request->header('x-transaction-id') . '| Disparando envio de Pixel via event|', $data);
            if (is_array($data) && isset($data['offer_id'])) {
                unset($data['offer_id']); // Remover 'offer_id' da raiz do array
            }
            event(new PixelEvent($request->offer_id, $request->event_name, $data, $request->header('x-transaction-id')));
            return  Responses::SUCCESS(' Pixel enviado para processamento',$data,201);
        }catch (\Exception $e) {
            Log::error($request->header('x-transaction-id').'| Não foi possível enviar o pixel|'.$e->getMessage());
            return Responses::ERROR('Não foi possível enviar o pixel', null,-1000);
        }
    }
    public function show(int $offer_id)
    {
        try {
            $pixels = PixelEventService::listAllPixels($offer_id);
            if($pixels->isNotEmpty()) {
              return  Responses::SUCCESS('Pixel listado com sucesso', $pixels, 200);
            }
            return Responses::SUCCESS('Não foi encontrado pixel para essa oferta', null, 200);
        }catch (\Throwable $th) {
            Log::error('Não foi possível Listar os Pixels dessa oferta', ['error' => $th->getMessage()]);
            return Responses::ERROR('Ocorreu um erro ao tentar Listar o Pixel', null, '-9999', 400);
        }
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
