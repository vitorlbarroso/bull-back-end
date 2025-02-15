<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Models\CelcashPayments;
use App\Services\UserPaymentsDataService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function dashboard(Request $request)
    {
        $initialDate = $request->query('initial_date', null);
        $finishDate = $request->query('finish_date', null);

        $user = Auth::user();

        $responseData = [
            'total_sales' => 0,
            'total_billed' => 0,
            'percentage_chargeback' => 0,
            'total_chargeback' => 0,
            'percentage_refund' => 0,
            'total_refund' => 0,
            'total_orderbump_sales' => 0,
            'total_card_sales' => 0,
            'total_pix_sales' => 0,
            'total_billet_sales' => 0,
            'total_pix_converted_sales' => 0,
            'graphics' => [
                'sales_period_graphic' => [
                    "categories" => ["0:00"],
                    "data" => [0, 0],
                ]
            ]
        ];

        $currentHour = Carbon::now()->startOfHour();
        $formattedHour = $currentHour->format('H:i');
        $responseData['graphics']['sales_period_graphic']['categories'][] = $formattedHour;

        /* Definindo total de pagamentos */
        $getAllSales = UserPaymentsDataService::getAllSales($initialDate, $finishDate);
        $responseData['total_sales'] = $getAllSales;

        if ($responseData['total_sales'] < 1 && (is_null($initialDate) || is_null($finishDate))) {
            return Responses::SUCCESS('', $responseData, 200);
        }

        /* Definindo total faturado */
        $getTotalBilled = UserPaymentsDataService::getTotalBilled($initialDate, $finishDate);
        $responseData['total_billed'] = $getTotalBilled / 100;

        /* Definindo reembolsos */
        $getTotalRefunds = UserPaymentsDataService::calculateTotalRefund($initialDate, $finishDate);
        $responseData['total_refund'] = $getTotalRefunds['total'];
        $responseData['percentage_refund'] = $getTotalRefunds['percentage'];

        /* Definindo total de orderbumps */
        $getTotalOrderbumps = UserPaymentsDataService::getTotalOrderbumpsSales($initialDate, $finishDate);
        $responseData['total_orderbump_sales'] = $getTotalOrderbumps;

        /* Definindo total de vendas por PIX */
        $getTotalPixSales = UserPaymentsDataService::getTotalPixSales($initialDate, $finishDate);
        $responseData['total_pix_sales'] = $getTotalPixSales;

        /* Definindo total de vendas por Cartão */
        $getTotalCardSales = UserPaymentsDataService::getTotalCardSales($initialDate, $finishDate);
        $responseData['total_card_sales'] = $getTotalCardSales;

        $getTotalPixConvertedSales = UserPaymentsDataService::getTotalPixConvertedSales($initialDate, $finishDate);
        $responseData['total_pix_converted_sales'] = $getTotalPixConvertedSales;

        /* Definindo dados do grafico */
        $getSalesToGraphic = UserPaymentsDataService::getPeriodSalesToGraphic($initialDate, $finishDate);
        $responseData['graphics']['sales_period_graphic']['categories'] = $getSalesToGraphic['categories'];
        $responseData['graphics']['sales_period_graphic']['data'] = $getSalesToGraphic['data'];

        return Responses::SUCCESS('', $responseData);
    }
}
