<?php

namespace App\Http\Controllers;

use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConfigController extends Controller
{

    public function insurance(Request $request){
        $request->validate([
            'insurance_amount' => 'required|numeric|min:0'
        ]);

        $amount = $request->insurance_amount;

        $config = Config::first();
        $config->insurance = $amount;
        $config->save();

        return response(['status' => true]);
    }

}
