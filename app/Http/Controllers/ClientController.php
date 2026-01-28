<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contract;
use App\Models\Quota;
use App\Models\User;

class ClientController extends Controller
{
    public function index(Request $request){
        $user = auth()->user();
        $sellers = User::seller()->where('state', 0)->active()->get();
        $clients = Contract::active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->where('seller_id', $user->id);
        })->when($request->name, function($query, $name){
            return $query->where('name', 'like', '%'.$name.'%');
        })->when($request->seller_id, function($query, $seller_id){
            return $query->where('seller_id', $seller_id);
        })->when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->latest('date')->latest('id')->groupBy('document')->groupBy('group_name')->paginate(20);
        
        return view('clients.index', compact('clients', 'sellers'));
    }

    public function check(Request $request){
        $quotas = Quota::whereHas('contract', function($query) use ($request){
            return $query->active()->where('document', $request->document);
        })->where('paid', 0)->orderBy('contract_id', 'asc')->orderBy('date', 'asc')->get();

        $status = true;

        if($quotas->count() > 0){
            $status = false;
        }

        $quotas = $quotas->map(function($quota){
            return [
                'id' => $quota->id,
                'contract_id' => $quota->contract_id,
                'number' => $quota->number,
                'amount' => $quota->amount,
                'debt' => $quota->debt,
                'date' => $quota->date->format('d/m/Y'),
                'paid' => $quota->paid
            ];
        });

        return response()->json([
            'status' => $status,
            'quotas' => $quotas
        ]);
    }

    public function details(Request $request){
        
        $client = Contract::active()->where('document', $request->document)->latest('date')->first();

        return response()->json([
            'id' => $client->id,
            'document' => $client->document,
            'name' => $client->name,
            'phone' => $client->phone,
            'address' => $client->address,
            'civil_status' => $client->civil_status
        ]);
    }

    public function contracts(Request $request){
        
        $user = auth()->user();
        $contracts = Contract::active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->where('seller_id', $user->id);
        })->when($request->client_type, function($query, $client_type) use($request){
            if($client_type == 'Personal'){
                return $query->where('document', $request->document);
            }elseif($client_type == 'Grupo'){
                return $query->where('group_name', $request->group_name);
            }
        })->with('quotas')->latest('date')->get();

        return response()->json($contracts->map(function($contract){
            return [
                'id' => $contract->id,
                'requested_amount' => $contract->requested_amount,
                'quotas_number' => $contract->quotas_number,
                'interest' => $contract->interest,
                'payable_amount' => $contract->payable_amount,
                'date' => $contract->date->format('d/m/Y'),
                'paid' => $contract->paid
            ];
        }));
    }

    public function quotas(Request $request){
        $contract_id = $request->contract_id;

        $quotas = Quota::where('contract_id', $contract_id)->get();

        return response()->json($quotas->map(function($quota){
            return [
                'id' => $quota->id,
                'number' => $quota->number,
                'amount' => $quota->amount,
                'debt' => $quota->debt,
                'date' => $quota->date->format('d/m/Y'),
                'paid' => $quota->paid
            ];
        }));
    }

    public function api(Request $request){
        $contracts = Contract::active()->where(function($query) use($request){
            return $query->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('document', 'like', '%'.$request->q.'%');
        })->where('client_type', 'Personal')->orderBy('name')->get();
        return response()->json(['items' => $contracts]);
    }
}
