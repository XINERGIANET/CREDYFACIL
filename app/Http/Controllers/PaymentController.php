<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\ChargesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Contract;
use App\Models\Quota;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;

class PaymentController extends Controller
{
    public function index(Request $request){
        $user = auth()->user();
        $payments = Payment::active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->whereHas('quota.contract', function($query) use($user){
                return $query->where('seller_id', $user->id);
            });
        })->when($request->name, function($query, $name){
            return $query->whereHas('quota.contract', function($query) use($name){
                return $query->where(function($query) use ($name){
                    return $query->where('name', 'like', '%'.$name.'%')->orWhere('group_name', 'like', '%'.$name.'%');
                });
            });
        })->when($request->payment_method_id, function($query, $payment_method_id){
            return $query->where('payment_method_id', $payment_method_id);
        })->when($request->seller_id, function($query, $seller_id){
            return $query->whereHas('quota.contract', function($query) use($seller_id){
                return $query->where('seller_id', $seller_id);
            });
        })->when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->latest('date')->latest('id');

        $total = $payments->sum('amount');

        $payments = $payments->paginate(20);

        $payment_methods = PaymentMethod::active()->get();
        $sellers = User::seller()->where('state', 0)->active()->get();

        $day = now()->format('N'); // 1-5
        $hour = now()->format('G'); // 7 - 20

        return view('payments.index', compact('payments', 'payment_methods', 'sellers', 'day', 'hour', 'total'));
    }

    public function charges(Request $request){
        $user = auth()->user();
        $sellers = User::seller()->active()->get();
        $quotas = Quota::active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->whereHas('contract', function($query) use($user){
                return $query->where('seller_id', $user->id);
            });
        })->when($request->name, function($query, $name){
            return $query->whereHas('contract', function($query) use($name){
                return $query->where(function($query) use ($name){
                    return $query->where('name', 'like', '%'.$name.'%')->orWhere('group_name', 'like', '%'.$name.'%');
                });
            });
        })->when($request->seller_id, function($query, $seller_id){
            return $query->whereHas('contract', function($query) use($seller_id){
                return $query->where('seller_id', $seller_id);
            });
        })->when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->where('paid', 0)->orderBy('date')->paginate(20);

        return view('payments.charges', compact('quotas', 'sellers'));
    }

    public function dues(Request $request){
        $user = auth()->user();
        $sellers = User::seller()->active()->get();
        $date = $request->date ? $request->date : now();
        $quotas = Quota::active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->whereHas('contract', function($query) use ($user){
                return $query->where('seller_id', $user->id);
            });
        })->when($request->name, function($query, $name){
            return $query->whereHas('contract', function($query) use($name){
                return $query->where('name', 'like', '%'.$name.'%');
            });
        })->when($request->seller_id, function($query, $seller_id){
            return $query->whereHas('contract', function($query) use($seller_id){
                return $query->where('seller_id', $seller_id);
            });
        })->when($request->from_days, function($query, $from_days){
            return $query->whereRaw('DATEDIFF(?, date) >= ?', [now()->format('Y-m-d'), $from_days]);
        })->when($request->to_days, function($query, $to_days){
            return $query->whereRaw('DATEDIFF(?, date) <= ?', [now()->format('Y-m-d'), $to_days]);
        })->whereDate('date', '<', $date)->where('paid', 0)->paginate(20);

        return view('payments.dues', compact('quotas', 'sellers'));
    }

    public function store(Request $request){

        $day = now()->format('N'); // 1-5
        $hour = now()->format('G'); // 7 - 20

        if(!auth()->user()->hasRole('admin') && ($day > 5 || $hour < 8 || $hour > 19)){
            return response()->json([
                'status' => false,
                'error' => 'El registro de pagos se encuentra fuera de horario.'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'quota_id' => 'required',
            'amount' => 'required|numeric|min:0.1',
            'payment_method_id' => 'required',
            'date' => 'required|date'
        ]);

        $quota = Quota::find($request->quota_id);

        if($quota){
            $contract= $quota->contract;
        }

        $validator->after(function($validator) use ($request, $quota){

            if($quota){
                if($request->amount > $quota->debt){
                    $validator->errors()->add('cart', 'El pago debe ser menor o igual al saldo pendiente');
                }
            }else{
                $validator->errors()->add('cart', 'La cuota no se encuentra');
            }

        });

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        DB::beginTransaction();

        try {

            $payment_date = Carbon::parse($request->date);

            $diff = $payment_date->diffInDays($quota->date, false);

            $due_days = $diff < 0 ? abs($diff) : 0;

            $people = null;

            if($contract->client_type == 'Grupo'){
                $payment_people = $request->people ? $request->people : [];

                $people = [];

                foreach($payment_people as $document){

                    foreach(json_decode($contract->people) as $client){
                        if($client->document == $document){
                            $people[] = $client;
                        }
                    }

                }

                $people = json_encode($people);
            }

            $image = null;

            if($request->hasFile('image')){
                $image = $request->image->store('payments', 'public');
            }
            
            Payment::create([
                'quota_id' => $request->quota_id,
                'amount' => $request->amount,
                'payment_method_id' => $request->payment_method_id,
                'date' => $request->date,
                'due_days' => $due_days,
                'image' => $image,
                'people' => $people
            ]);

            $paid = $request->amount == $quota->debt ? 1 : 0;

            $quota->update([
                'debt' => $quota->debt - $request->amount,
                'paid' => $paid
            ]);

            $quotas = Quota::where('contract_id', $quota->contract_id)->where('paid', 0);

            if($quotas->count() == 0){
                $quota->contract()->update([
                  'paid' => 1
                ]);
            }

            DB::commit();

        }catch(Exception $e){
            DB::rollBack();

            return response()->json([
                'status' => false
            ]);
        }

        return response()->json([
            'status' => true
        ]);
        
    }

    public function edit(Request $request, Payment $payment){
        return response()->json([
            'id' => $payment->id,
            'client' => optional(optional($payment->quota)->contract)->client(),
            'quota' => $payment->quota,
            'amount' => $payment->amount,
            'payment_method_id' => $payment->payment_method_id,
            'date' => $payment->date->format('d/m/Y')
        ]);
    }

    public function update(Request $request, Payment $payment){
        $validator = Validator::make($request->all(), [
            'payment_method_id' => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $payment->update([
            'payment_method_id' => $request->payment_method_id
        ]);

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, Payment $payment){

        DB::beginTransaction();

        try {

            $payment->update(['deleted' => 1]);

            $quota = $payment->quota;

            $quota->update([
                'debt' => $quota->debt + $payment->amount,
                'paid' => 0
            ]);

            $quota->contract()->update([
                'paid' => 0
            ]);

            DB::commit();

        }catch(Exception $e){
            DB::rollBack();

            return response()->json([
                'status' => false
            ]);
        }

        

        return response()->json([
            'status' => true
        ]);
    }

    public function chargesExcel(Request $request){
        $name = "GestionDeCobranza_".now()->format('d_m_Y').".xlsx";
        return Excel::download(new ChargesExport, $name);
    }
}
