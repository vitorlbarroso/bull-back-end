<?php

namespace App\Services;

use App\Models\CelcashPayments;
use App\Models\User;
use App\Models\WithdrawalRequests;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserPaymentsDataService
{
    static function convertDate($initialDate, $finishDate)
    {
        if (is_null($initialDate) || is_null($finishDate)) {
            $initialDate = Carbon::today()->startOfDay();
            $finishDate = Carbon::today()->endOfDay();
        } else {
            // Converte as datas para o formato Carbon
            $initialDate = Carbon::parse($initialDate)->startOfDay();
            $finishDate = Carbon::parse($finishDate)->endOfDay();
        }

        return [$initialDate, $finishDate];
    }

    static public function getAllSales($initialDate = null, $finishDate = null)
    {
        $user = Auth::user();

        [$initialDate, $finishDate] = self::convertDate($initialDate, $finishDate);

        try {
            $getAllSales = CelcashPayments::where('receiver_user_id', $user->id)
                ->whereBetween('created_at', [$initialDate, $finishDate])
                ->where(function ($query) {
                    $query->where('status', 'payed_pix')
                        ->orWhere('status', 'authorized');
                })
                ->count();

            return $getAllSales;
        }
        catch (\Exception $e) {
            Log::error('Ocorreu um erro ao buscar as vendas do usuário ' . $user->id, ['error' => $e->getMessage()]);
        }

        return 0;
    }

    static public function getTotalBilled($initialDate = null, $finishDate = null) : int
    {
        $user = Auth::user();

        [$initialDate, $finishDate] = self::convertDate($initialDate, $finishDate);

        try {
            $getTotalBilled = CelcashPayments::where('receiver_user_id', $user->id)
                ->whereBetween('created_at', [$initialDate, $finishDate])
                ->where(function ($query) {
                    $query->where('status', 'payed_pix')
                        ->orWhere('status', 'authorized');
                })
                ->sum('value_to_receiver');

            return $getTotalBilled;
        }
        catch (\Exception $e) {
            Log::error('Ocorreu um erro ao buscar o total de faturamento do usuário ' . $user->id, ['error' => $e->getMessage()]);
        }

        return 0;
    }

    static public function calculateTotalRefund($initialDate = null, $finishDate = null) : array
    {
        $user = Auth::user();

        [$initialDate, $finishDate] = self::convertDate($initialDate, $finishDate);

        $returnData = [
            'total' => 0,
            'percentage' => 0
        ];

        try {
            $totalPayments = CelcashPayments::where('status', 'payed_pix')
                ->where('receiver_user_id', $user->id)
                ->whereBetween('created_at', [$initialDate, $finishDate])
                ->count();

            $totalRefunded = CelcashPayments::where('status', 'refunded')
                ->where('receiver_user_id', $user->id)
                ->whereBetween('created_at', [$initialDate, $finishDate])
                ->count();

            if ($totalPayments + $totalRefunded > 0) {
                $percentageRefunded = ($totalRefunded / ($totalPayments + $totalRefunded)) * 100;

                $returnData['total'] = $totalRefunded;
                $returnData['percentage'] = $percentageRefunded;
            }
        }
        catch (\Exception $e) {
            Log::error('Ocorreu um erro ao buscar o total de reembolsos do usuário ' . $user->id, ['error' => $e->getMessage()]);
        }

        return $returnData;
    }

    static public function calculateTotalChargeback($initialDate = null, $finishDate = null) : array
    {
        $user = Auth::user();

        [$initialDate, $finishDate] = self::convertDate($initialDate, $finishDate);

        $returnData = [
            'total' => 0,
            'percentage' => 0
        ];

        try {
            $totalPayments = CelcashPayments::where('status', 'payed_pix')
                ->orWhere('status', 'chargeback')
                ->orWhere('status', 'reversed')
                ->where('receiver_user_id', $user->id)
                ->whereBetween('created_at', [$initialDate, $finishDate])
                ->count();

            $totalChargeback = CelcashPayments::where('status', 'chargeback')
                ->orWhere('status', 'reversed')
                ->where('receiver_user_id', $user->id)
                ->whereBetween('created_at', [$initialDate, $finishDate])
                ->count();

            if ($totalPayments + $totalChargeback > 0) {
                $percentageChargeback = ($totalChargeback / ($totalPayments + $totalChargeback)) * 100;

                $returnData['total'] = $totalChargeback;
                $returnData['percentage'] = round($percentageChargeback, 2);
            }
        }
        catch (\Exception $e) {
            Log::error('Ocorreu um erro ao buscar o total de chargebacks do usuário ' . $user->id, ['error' => $e->getMessage()]);
        }

        return $returnData;
    }

    static public function getTotalOrderbumpsSales($initialDate = null, $finishDate = null) : int
    {
        $user = Auth::user();

        [$initialDate, $finishDate] = self::convertDate($initialDate, $finishDate);

        try {
            $getTotalOrderbumpsSales = DB::table('celcash_payments_offers')
                ->join('celcash_payments', 'celcash_payments.id', '=', 'celcash_payments_offers.celcash_payments_id')
                ->where('celcash_payments.receiver_user_id', $user->id)
                ->where('celcash_payments_offers.type', 'orderbump')
                ->whereBetween('celcash_payments.created_at', [$initialDate, $finishDate])
                ->where('celcash_payments.status', 'payed_pix')
                ->orWhere('celcash_payments.status', 'authorized')
                ->count();

            return $getTotalOrderbumpsSales;
        }
        catch (\Exception $e) {
            Log::error('Ocorreu um erro ao buscar o total de orderbumps do usuário ' . $user->id, ['error' => $e->getMessage()]);
        }

        return 0;
    }

    static public function getTotalPixConvertedSales($initialDate = null, $finishDate = null) : float
    {
        $user = Auth::user();

        [$initialDate, $finishDate] = self::convertDate($initialDate, $finishDate);

        try {
            $successfulPixPayments = CelcashPayments::where('receiver_user_id', $user->id)
                ->where('type', 'pix')
                ->where('status', 'payed_pix')
                ->whereBetween('created_at', [$initialDate, $finishDate])
                ->count();

            $totalPixPayments = CelcashPayments::where('receiver_user_id', $user->id)
                ->where('type', 'pix')
                ->whereBetween('created_at', [$initialDate, $finishDate])
                ->count();

            $conversionRate = $totalPixPayments > 0 ? ($successfulPixPayments / $totalPixPayments) * 100 : 0;

            return round($conversionRate, 1);
        } catch (\Exception $e) {
            Log::error('Erro ao calcular taxa de conversão PIX para o usuário ' . $user->id, [
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    static public function getTotalPixSales($initialDate = null, $finishDate = null) : int
    {
        $user = Auth::user();

        [$initialDate, $finishDate] = self::convertDate($initialDate, $finishDate);

        try {
            $getTotalPixSales = CelcashPayments::where('receiver_user_id', $user->id)
                ->where('type', 'pix')
                ->whereBetween('created_at', [$initialDate, $finishDate])
                ->where('status', 'payed_pix')
                ->count();

            return $getTotalPixSales;
        }
        catch (\Exception $e) {
            Log::error('Ocorreu um erro ao buscar o total de vendas PIX do usuário ' . $user->id, ['error' => $e->getMessage()]);
        }

        return 0;
    }
    static public function getTotalCardSales($initialDate = null, $finishDate = null) : int
    {
        $user = Auth::user();

        [$initialDate, $finishDate] = self::convertDate($initialDate, $finishDate);

        try {
            $getTotalPixSales = CelcashPayments::where('receiver_user_id', $user->id)
                ->where('type', 'card')
                ->whereBetween('created_at', [$initialDate, $finishDate])
                ->where('status', 'authorized')
                ->count();

            return $getTotalPixSales;
        }
        catch (\Exception $e) {
            Log::error('Ocorreu um erro ao buscar o total de vendas PIX do usuário ' . $user->id, ['error' => $e->getMessage()]);
        }

        return 0;
    }

    static public function getPeriodSalesToGraphic($initialDate = null, $finishDate = null)
    {
        $user = Auth::user();

        [$initialDate, $finishDate] = self::convertDate($initialDate, $finishDate);

        $categories = [];
        $data = [];

        $dateDiffInDays = $initialDate->diffInDays($finishDate);

        if ($dateDiffInDays > 0) {
            // Agrupamento por dia na tabela celcash_payments
            $sales = DB::table('celcash_payments')
                ->where('celcash_payments.receiver_user_id', $user->id)
                ->selectRaw('DATE(created_at) as date, SUM(value_to_receiver) as total_value')
                ->whereBetween('created_at', [$initialDate, $finishDate])
                ->where('celcash_payments.status', 'payed_pix')
                ->orWhere('celcash_payments.status', 'authorized')
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get();

            // Preenche os arrays com base no intervalo diário
            for ($date = $initialDate->copy(); $date <= $finishDate; $date->addDay()) {
                $formattedDate = $date->format('d/m');
                $categories[] = $formattedDate;

                // Verifica se houve vendas naquele dia
                $sale = $sales->firstWhere('date', $date->format('Y-m-d'));
                $totalValue = $sale ? $sale->total_value / 100 : 0;
                $data[] = $totalValue;
            }

        } else {
            $currentHour = Carbon::now()->format('H:00');

            $initialDate = Carbon::today(); // 00:00 de hoje
            $finishDate = Carbon::now(); // Horário atual

            $sales = DB::table('celcash_payments')
                ->selectRaw('HOUR(created_at) as hour, SUM(value_to_receiver) as total_value')
                ->whereBetween('created_at', [$initialDate, $finishDate])
                ->where('celcash_payments.status', 'payed_pix')
                ->orWhere('celcash_payments.status', 'authorized')
                ->groupBy(DB::raw('HOUR(created_at)'))
                ->get();

            $categories = ['00:00'];
            $data = [];

            $saleAtMidnight = $sales->firstWhere('hour', 0);
            if ($saleAtMidnight) {
                $data[] = $saleAtMidnight->total_value / 100;
            } else {
                $data[] = '0,00';
            }

            foreach ($sales as $sale) {
                if ($sale->hour > 0) {
                    $formattedHour = str_pad($sale->hour, 2, '0', STR_PAD_LEFT) . ":00";
                    $categories[] = $formattedHour;

                    $totalValue = $sale->total_value / 100;
                    $data[] = $totalValue;
                }
            }

            if (!in_array($currentHour, $categories)) {
                $categories[] = $currentHour;

                $saleAtCurrentHour = $sales->firstWhere('hour', Carbon::now()->hour);
                if ($saleAtCurrentHour) {
                    $data[] = $saleAtCurrentHour->total_value / 100;
                } else {
                    $data[] = '0,00';
                }
            }

            if (count($sales) === 0) {
                $categories = ['00:00', $currentHour];
                $data = ['0,00', '0,00'];
            }
        }

        return [
            'categories' => $categories,
            'data' => $data,
        ];
    }

    static public function getUserTotalSalesInAllPeriod() : int
    {
        $user = Auth::user();

        try {
            $getAllPayments = CelcashPayments::where('receiver_user_id', $user->id)
                ->where('status', 'payed_pix')
                ->orWhere('status', 'authorized')
                ->sum('value_to_receiver');

            return $getAllPayments;
        }
        catch (\Exception $e) {
            Log::error('Ocorreu um erro ao buscar o total de vendas do usuário ' . $user->id, ['error' => $e->getMessage()]);
        }

        return 0;
    }

    static public function getWithdrawalData(): array
    {
        $user = Auth::user();
        $returnData = [
            'total_amount' => 0,
            'total_pending' => 0,
            'total_available' => 0,
            'total_chargeback' => 0,
            'total_chargeback_count' => 0,
            'withdrawal_tax' => 0
        ];

        try {
            $currentDate = Carbon::now();
            $periodDays = $currentDate->copy()->subDays($user->withdrawal_period);

            // Agrupando consultas em uma única query para otimizar
            $paymentData = CelcashPayments::where('receiver_user_id', $user->id)
                ->selectRaw("
                SUM(CASE WHEN status IN ('payed_pix', 'authorized') THEN value_to_receiver ELSE 0 END) as total_payments,
                SUM(CASE WHEN status IN ('chargeback', 'reversed') THEN value_to_receiver ELSE 0 END) as total_chargebacks,
                SUM(CASE WHEN status IN ('payed_pix', 'authorized') AND created_at >= ? THEN value_to_receiver ELSE 0 END) as total_pending_payments,
                COUNT(CASE WHEN status IN ('chargeback', 'reversed') THEN 1 ELSE NULL END) as total_chargebacks_count
            ", [$periodDays])
                ->first();

            // Buscar retiradas (Withdraws) de forma otimizada
            $totalWithdraws = WithdrawalRequests::where('user_id', $user->id)
                ->whereIn('status', ['effected', 'pending', 'approved_effect'])
                ->sum('withdrawal_amount');

            // Conversão de centavos para reais
            $totalPayments = $paymentData->total_payments / 100;
            $totalChargebacks = $paymentData->total_chargebacks / 100;
            $totalPendingPayments = $paymentData->total_pending_payments / 100;
            $totalChargebacksCount = $paymentData->total_chargebacks_count;

            // Cálculo do valor total disponível
            $totalAmount = ($totalPayments - ($totalWithdraws / 100)) - $totalChargebacks;
            $availablePayments = max(($totalAmount - $totalPendingPayments), 0);

            // Atribuir valores ao array de retorno
            $returnData['total_amount'] = $totalAmount;
            $returnData['total_pending'] = $totalPendingPayments;
            $returnData['total_available'] = round($availablePayments, 2);
            $returnData['total_chargeback'] = $totalChargebacks;
            $returnData['total_chargeback_count'] = $totalChargebacksCount;
            $returnData['withdrawal_tax'] = $user->withdrawal_tax;
        } catch (\Exception $e) {
            \Log::error('Erro ao calcular dados de saque: ' . $e->getMessage());
        }

        return $returnData;
    }

}
