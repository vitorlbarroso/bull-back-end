<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Http\Helpers\Responses;
use App\Http\Requests\Timer\TimerRequest;
use App\Models\Timer;
use App\Services\TimerService;
use Illuminate\Support\Facades\Log;


class TimerController extends Controller
{
    protected TimerService $timerService;

    public function __construct(TimerService $timerService)
    {
        $this->timerService = $timerService;
    }


    public function store()
    {

        try {
            $timer = $this->timerService->createTimer();
            return response()->json(['message' => 'Timer criado com sucesso', 'timer' => $timer], 201);
        } catch (CustomException $e) {
            return response()->json(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (\Exception $e) {
            Log::error("|" . request()->header('x-transaction-id') . "| Error ao Criar novo Timer", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erro ao Criar o Timer'], 500);
        }

    }

    public function show(Timer $timer)
    {
        return $timer;
    }

    public function update(TimerRequest $request, Timer $timer)
    {
        $timer->update($request->validated());

        return $timer;
    }

    public function destroy(Timer $timer)
    {
        $timer->delete();

        return response()->json();
    }
}
