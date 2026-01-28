<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Transfer;
use App\Models\User;
use App\Models\PaymentMethod;

class TransferController extends Controller
{
    public function index(Request $request){
        $transfers = Transfer::active()->when($request->type, function($query, $type){
            return $query->where('type', $type);
        })->when($request->seller_id, function($query, $seller_id){
            return $query->where(function($query) use($seller_id){
                return $query->where('from_seller_id', $seller_id)->orWhere('to_seller_id', $seller_id);
            });
        })->when($request->payment_method_id, function($query, $payment_method_id){
            return $query->where(function($query) use($payment_method_id){
                return $query->where('from_payment_method_id', $payment_method_id)->orWhere('to_payment_method_id', $payment_method_id);
            });
        })->when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->latest('date')->paginate(20);

        $sellers = User::seller()->where('state', 0)->active()->get();
        $payment_methods = PaymentMethod::all();
        
        return view('transfers.index', compact('transfers', 'sellers', 'payment_methods'));
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'from_seller_id' => 'nullable',
            'to_seller_id' => 'nullable',
            'from_payment_method_id' => 'nullable',
            'to_payment_method_id' => 'nullable',
            'amount' => 'required|numeric',
            'reason' => 'nullable|string'
        ]);

        $validator->sometimes(['from_seller_id', 'to_seller_id'], 'required', function ($request) {
            return $request->type == 'seller';
        });

        $validator->sometimes(['from_payment_method_id', 'to_payment_method_id'], 'required', function ($request) {
            return $request->type == 'payment_method';
        });

        $validator->after(function($validator) use ($request){

            if($request->type == 'seller'){
                if($request->from_seller_id == $request->to_seller_id){
                    $validator->errors()->add('from_seller_id', 'El origen y destino no pueden ser iguales');
                }
            }elseif($request->type == 'payment_method'){
                if($request->from_payment_method_id == $request->to_payment_method_id){
                    $validator->errors()->add('from_seller_id', 'El origen y destino no pueden ser iguales');
                }
            }

        });

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $transfer = new Transfer;
        
        $transfer->type = $request->type;

        if($request->type == 'seller'){

            $transfer->from_seller_id = $request->from_seller_id;
            $transfer->to_seller_id = $request->to_seller_id;

        }elseif($request->type == 'payment_method'){

            $transfer->from_payment_method_id = $request->from_payment_method_id;
            $transfer->to_payment_method_id = $request->to_payment_method_id;

        }

        $transfer->amount = $request->amount;
        $transfer->reason = $request->reason;
        $transfer->date = now();

        $transfer->save();

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, Transfer $transfer){
        return response()->json($transfer);
    }

    public function update(Request $request, Transfer $transfer){
        $validator = Validator::make($request->all(), [
            'type' => 'required',
            'from_seller_id' => 'nullable',
            'id_seller_id' => 'nullable',
            'from_payment_method_id' => 'nullable',
            'id_payment_method_id' => 'nullable',
            'amount' => 'required|numeric',
            'reason' => 'nullable|string'
        ]);

        $validator->sometimes(['from_seller_id', 'to_seller_id'], 'required', function ($request) {
            return $request->type == 'seller';
        });

        $validator->sometimes(['from_payment_method_id', 'to_payment_method_id'], 'required', function ($request) {
            return $request->type == 'payment_method';
        });

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $transfer->update($request->all());

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, Transfer $transfer){
        $transfer->update(['deleted' => 1]);

        return response()->json([
            'status' => true
        ]);
    }

}
