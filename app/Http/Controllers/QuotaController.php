<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Contract;
use App\Models\Quota;

class QuotaController extends Controller
{
    public function api(Request $request){
        $contract = Contract::findOrFail($request->contract_id);
        $quotas = Quota::where('contract_id', $request->contract_id)->where('paid', 0)->get();
        $quotas = $quotas->map(function($quota){
            return [
                'id' => $quota->id,
                'number' => $quota->number,
                'date' => $quota->date->format('d/m/Y'),
                'amount' => $quota->amount,
                'debt' => $quota->debt,
            ];
        });

        return response()->json([
            'contract' => $contract,
            'quotas' => $quotas
        ]);
    }
}
