<?php

namespace App\Services;

use App\Models\Timer;
use Illuminate\Support\Facades\Log;
class TimerService
{
    protected array $timer_data;

    public function __construct()
    {
        $this->timer_data = [
            'timer_title' => 'Última vagas!',
            'timer_title_color' => '#FFFFFF',
            'timer_icon_color' => '#FFFFFF',
            'timer_bg_color' => '#000000',
            'timer_progressbar_bg_color' => '#000000',
            'timer_progressbar_color' => '#FFFFFF',
            'countdown' => '00:15:00',
            'end_timer_title' => 'Finalize sua compra!',
            'display' => true,
            'is_fixed' => true
        ];
    }

    public function createTimer()
    {
        try {
            $timer = Timer::create($this->timer_data);

            Log::info("|" . request()->header('x-transaction-id') . "| Timer gerado na criação do checkout", ['Timer' => $timer]);

            return $timer;
        } catch (\Exception $e) {
            Log::error("|" . request()->header('x-transaction-id') . "| Error ao Criar novo Timer para o checkout", ['error' => $e->getMessage()]);

            throw new CustomException("Erro ao Criar o Timer", null, -1000, 400);
        }
    }

    public function getAllTimers()
    {
        return Timer::all();
    }
    protected function formatTimerData(array $data): array
    {
        return [
            'is_fixed' => $data['timer']['is_fixed'],
            'countdown' => $data['timer']['countdown'],
            'display' => $data['timer']['display'],
            'end_timer_title' => $data['timer']['end_timer_title'],
            'timer_title' => $data['timer']['timer_title'],
            'timer_title_color' => $data['timer']['timer_title_color'],
            'timer_bg_color' => $data['timer']['timer_bg_color'],
            'timer_icon_color' => $data['timer']['timer_icon_color'],
            'timer_progressbar_bg_color' => $data['timer']['timer_progressbar_bg_color'],
            'timer_progressbar_color' => $data['timer']['timer_progressbar_color'],
        ];
    }

    public function updateTimerService($id, $data)
    {
        $timer = Timer::find($id);
        if ($timer) {
            $timerUpdate = $this->formatTimerData($data);
            $timer->update($timerUpdate);
        }
    }
}
