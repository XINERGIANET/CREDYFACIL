<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Exports\ExpensesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Expense;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\ExpensePayment;

class ExpenseController extends Controller
{
    public function index(Request $request){
        $user = auth()->user();
        $expensesQuery = Expense::with('expensePayments.paymentMethod')->active()
            ->when($user->hasRole('seller'), function($query) use($user){
                return $query->where('seller_id', $user->id);
            })->when($request->description, function($query, $description){
                return $query->where('description', 'like', '%'.$description.'%');
            })->when($request->seller_id, function($query, $seller_id){
                return $query->where('seller_id', $seller_id);
            })->when($request->payment_method_id, function($query, $payment_method_id){
                return $query->whereHas('expensePayments', function($q) use ($payment_method_id){
                    $q->where('payment_method_id', $payment_method_id);
                });
            })->when($request->start_date, function($query, $start_date){
                return $query->whereDate('date', '>=', $start_date);
            })->when($request->end_date, function($query, $end_date){
                return $query->whereDate('date', '<=', $end_date);
            })->latest('date')->latest('id');

        // calcular total desde expense_payments para los gastos filtrados
        $expenseIds = $expensesQuery->pluck('id')->toArray();
        $total = 0;
        if (!empty($expenseIds)) {
            $total = ExpensePayment::whereIn('expenses_id', $expenseIds)->sum('amount');
        }

        $expenses = $expensesQuery->paginate(20);

        $sellers = User::seller()->where('state', 0)->active()->get();
        $payment_methods = PaymentMethod::all();
        
        return view('expenses.index', compact('expenses', 'sellers', 'payment_methods', 'total'));
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'payment_method_id' => 'required',
            'payment_amount' => 'required|numeric',
            'date' => 'required|date'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $image = null;

        if($request->hasFile('image')){
            $image = $request->image->store('expenses', 'public');
        }

        $expense = Expense::create([
            'description' => $request->description,
            'seller_id' => $request->seller_id,
            'contract_id' => $request->contract_id,
            'date' => $request->date,
            'image' => $image
        ]);

        // Guardar métodos de pago asociados (tabla expenses_payments) con montos
        if ($expense) {
            ExpensePayment::where('expenses_id', $expense->id)->delete();

            if ($request->payment_method_id) {
                ExpensePayment::create([
                    'expenses_id' => $expense->id,
                    'payment_method_id' => $request->payment_method_id,
                    'amount' => $request->payment_amount
                ]);
            }

            if ($request->payment_method_id_2 && $request->payment_amount_2) {
                ExpensePayment::create([
                    'expenses_id' => $expense->id,
                    'payment_method_id' => $request->payment_method_id_2,
                    'amount' => $request->payment_amount_2
                ]);
            }
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function edit(Request $request, Expense $expense){
        // obtener pagos asociados y mapear al primer y segundo (si existen)
        $payments = $expense->expensePayments()->get();
        $first = $payments->get(0);
        $second = $payments->get(1);

        return response()->json([
            'id' => $expense->id,
            'description' => $expense->description,
            'seller_id' => $expense->seller_id,
            'amount' => $expense->expensePayments->sum('amount'),
            'payment_method_id' => $first ? $first->payment_method_id : null,
            'payment_amount' => $first ? $first->amount : null,
            'payment_method_id_2' => $second ? $second->payment_method_id : null,
            'payment_amount_2' => $second ? $second->amount : null,
            'date' => $expense->date->format('Y-m-d')
        ]);
    }

    public function update(Request $request, Expense $expense){

        $validator = Validator::make($request->all(), [
            'description' => 'required',
            'payment_method_id' => 'required',
            'payment_amount' => 'required|numeric',
            'date' => 'required|date'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first()
            ]);
        }

        $image = $expense->image;

        if($request->hasFile('image')){
            $image = $request->image->store('expenses', 'public');
        }


        $expense->update([
            'description' => $request->description,
            'seller_id' => $request->seller_id,
            'date' => $request->date,
            'image' => $image
        ]);

        // sincronizar expenses_payments: eliminar y volver a crear según lo enviado (con montos)
        ExpensePayment::where('expenses_id', $expense->id)->delete();

        if ($request->payment_method_id) {
            ExpensePayment::create([
                'expenses_id' => $expense->id,
                'payment_method_id' => $request->payment_method_id,
                'amount' => $request->payment_amount
            ]);
        }

        if ($request->payment_method_id_2 && $request->payment_amount_2) {
            ExpensePayment::create([
                'expenses_id' => $expense->id,
                'payment_method_id' => $request->payment_method_id_2,
                'amount' => $request->payment_amount_2
            ]);
        }

        return response()->json([
            'status' => true
        ]);
    }

    public function destroy(Request $request, Expense $expense){
        $expense->update(['deleted' => 1]);

        return response()->json([
            'status' => true
        ]);
    }

    public function excel(Request $request){
        $name = "Egresos_".now()->format('d_m_Y').".xlsx";
        return Excel::download(new ExpensesExport, $name);
    }

}
