<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Responses;
use App\Models\BanksCode;
use Illuminate\Http\Request;

class BanksCodeController extends Controller
{
    public function index()
    {
        $getAllCodeBanks = BanksCode::where('is_active', 1)->get(['id', 'name', 'code']);

        return Responses::SUCCESS('', $getAllCodeBanks);
    }
}
