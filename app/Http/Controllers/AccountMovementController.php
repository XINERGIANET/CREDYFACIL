<?php

namespace App\Http\Controllers;

use App\Models\AccountMovement;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountMovementController extends Controller
{
    public function index(Request $request)
    {
        $movements = AccountMovement::with('paymentMethod')
            ->active()
            ->when($request->payment_method_id, function ($query, $paymentMethodId) {
                return $query->where('payment_method_id', $paymentMethodId);
            })
            ->when($request->type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($request->start_date, function ($query, $startDate) {
                return $query->whereDate('date', '>=', $startDate);
            })
            ->when($request->end_date, function ($query, $endDate) {
                return $query->whereDate('date', '<=', $endDate);
            })
            ->latest('date')
            ->latest('id')
            ->paginate(20);

        $payment_methods = PaymentMethod::active()->get();
        $balances = app(WebController::class)->accountBalances();

        return view('account_movements.index', compact('movements', 'payment_methods', 'balances'));
    }

    public function store(Request $request)
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return back()->withInput()->with('error', $validator->errors()->first());
        }

        AccountMovement::create($request->only([
            'payment_method_id',
            'type',
            'amount',
            'description',
            'date',
        ]));

        return back()->with('message', 'Movimiento registrado.');
    }

    public function edit(AccountMovement $accountMovement)
    {
        return response()->json($accountMovement);
    }

    public function update(Request $request, AccountMovement $accountMovement)
    {
        $validator = $this->validator($request);

        if ($validator->fails()) {
            return back()->withInput()->with('error', $validator->errors()->first());
        }

        $accountMovement->update($request->only([
            'payment_method_id',
            'type',
            'amount',
            'description',
            'date',
        ]));

        return back()->with('message', 'Movimiento actualizado.');
    }

    public function destroy(AccountMovement $accountMovement)
    {
        $accountMovement->update(['deleted' => 1]);

        return back()->with('message', 'Movimiento eliminado.');
    }

    private function validator(Request $request)
    {
        return Validator::make($request->all(), [
            'payment_method_id' => 'required|exists:payment_methods,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);
    }
}
