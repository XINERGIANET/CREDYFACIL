<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Contract;
use App\Models\Quota;

class InterestController extends Controller
{
    public function index(Request $request){
        $month = $request->month;
        $year = $request->year ?? date('Y');
        $clients = Contract::active()->when($request->name, function($query, $name){
            return $query->where('name', 'like', '%'.$name.'%')->orWhere('group_name', 'like', '%'.$name.'%');
        })->when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->latest('date')->latest('id')->groupBy('document')->groupBy('group_name')->paginate(20);
        
        $clients->getCollection()->transform(function($contract) use ($month, $year){
            $quotasNumber = (int) ($contract->quotas_number);
            $contractInterest = (float) ($contract->interest);

            // interés por cuota (proteger división por cero)
            $interestPerQuota = $quotasNumber > 0 ? ($contractInterest / $quotasNumber) : 0;

            // contar cuotas en el mes seleccionado, o todas si no hay mes
            if ($month) {
                $quotasCount = Quota::where('contract_id', $contract->id)
                    ->whereMonth('date', $month)
                    ->whereYear('date',$year)
                    ->count();
            } else {
                $quotasCount = Quota::where('contract_id', $contract->id)->count();
            }

            // interés total para el contrato según la regla
            $contract->filtered_interest = $interestPerQuota * $quotasCount;

            return $contract;
        });

        return view('interests.index', compact('clients'));
    }

    public function store(Request $request){
        // 
    }

    public function edit(Request $request, Transfer $transfer){
        // 
    }

    public function update(Request $request, Transfer $transfer){
        // 
    }

    public function destroy(Request $request, Transfer $transfer){
        //    
    }

}
