<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Exports\ExpensesExport;
use App\Exports\ExpensesCashExport;
use App\Exports\DisbursementsDailyExport;
use App\Services\DisbursementDailyService;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Models\Expense;
use App\Models\Contract;
use App\Models\DisbursementCheck;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\ExpensePayment;

class ExpenseController extends Controller
{
    public function index(Request $request, DisbursementDailyService $disbursementService){
        $user = auth()->user();
        $dailyDate = $request->daily_date ?: now()->format('Y-m-d');
        $dailyCarbon = Carbon::parse($dailyDate);
        $dailySellerId = $request->daily_seller_id ?: null;

        $dailyRows = $disbursementService->buildDailyRows($dailyCarbon, $user, $dailySellerId);
        $dayExpenses = Expense::with('expensePayments.paymentMethod')->active()
            ->whereNotNull('contract_id')
            ->whereDate('date', $dailyCarbon)
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($dailySellerId, function ($query, $dailySellerId) {
                return $query->where('seller_id', $dailySellerId);
            })
            ->get();
        $dailySummary = $disbursementService->summary($dailyRows, $dayExpenses);
        $dayBcpPayments = $disbursementService->dayBcpPayments($dailyCarbon, $user, $dailySellerId);

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
        })->latest('date')->latest('id')
            ->whereNotNull('contract_id');

        // calcular total desde expense_payments para los gastos filtrados
        $expenseIds = $expensesQuery->pluck('id')->toArray();
        $total = 0;
        if (!empty($expenseIds)) {
            $total = ExpensePayment::whereIn('expenses_id', $expenseIds)
                ->when($request->payment_method_id, function($query, $payment_method_id){
                    return $query->where('payment_method_id', $payment_method_id);
                })->sum('amount');
        }

        $expenses = $expensesQuery->paginate(20);

        $sellers = User::seller()->where('state', 0)->active()->get();
        $payment_methods = PaymentMethod::all();
        
        return view('expenses.index', compact(
            'expenses',
            'sellers',
            'payment_methods',
            'total',
            'dailyDate',
            'dailyRows',
            'dailySummary',
            'dayBcpPayments',
            'dailySellerId'
        ));
    }

    public function dailyContractDetail(Request $request, Contract $contract, DisbursementDailyService $disbursementService)
    {
        $date = $request->date ?: now()->format('Y-m-d');
        $user = auth()->user();

        if ($user->hasRole('seller') && (int) $contract->seller_id !== (int) $user->id) {
            return response()->json(['status' => false, 'error' => 'No autorizado'], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $disbursementService->contractDetail($contract, Carbon::parse($date)),
        ]);
    }

    public function contractInfo(Request $request, Contract $contract, DisbursementDailyService $disbursementService)
    {
        $date = $request->date ?: ($contract->date ? $contract->date->format('Y-m-d') : now()->format('Y-m-d'));
        $user = auth()->user();

        if ($user->hasRole('seller') && (int) $contract->seller_id !== (int) $user->id) {
            return response()->json(['status' => false, 'error' => 'No autorizado'], 403);
        }

        $data = $disbursementService->enrichContract(
            $contract->load(['seller', 'expenses.expensePayments.paymentMethod']),
            Carbon::parse($date),
            false
        );

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    public function toggleDailyCheck(Request $request, DisbursementDailyService $disbursementService)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'date' => 'required|date',
            'marked' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'error' => $validator->errors()->first(),
            ]);
        }

        $contract = Contract::findOrFail($request->contract_id);
        $user = auth()->user();

        if ($user->hasRole('seller') && (int) $contract->seller_id !== (int) $user->id) {
            return response()->json(['status' => false, 'error' => 'No autorizado'], 403);
        }

        $check = DisbursementCheck::updateOrCreate(
            [
                'contract_id' => $request->contract_id,
                'date' => $request->date,
            ],
            [
                'marked' => (bool) $request->marked,
                'user_id' => auth()->id(),
            ]
        );

        return response()->json([
            'status' => true,
            'marked' => (bool) $check->marked,
        ]);
    }

    public function excelDaily(Request $request, DisbursementDailyService $disbursementService)
    {
        $date = $request->daily_date ?: now()->format('Y-m-d');
        $user = auth()->user();
        $rows = $disbursementService->buildDailyRows(Carbon::parse($date), $user, $request->daily_seller_id);
        $dayExpenses = Expense::with('expensePayments')->active()
            ->whereNotNull('contract_id')
            ->whereDate('date', $date)
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($request->daily_seller_id, function ($query, $sellerId) {
                return $query->where('seller_id', $sellerId);
            })
            ->get();
        $summary = $disbursementService->summary($rows, $dayExpenses);
        $name = 'Desembolsos_dia_' . Carbon::parse($date)->format('d_m_Y') . '.xlsx';

        return Excel::download(new DisbursementsDailyExport($rows->values()->all(), $summary, $date), $name);
    }

    public function pdfDaily(Request $request, DisbursementDailyService $disbursementService)
    {
        $date = $request->daily_date ?: now()->format('Y-m-d');
        $user = auth()->user();
        $rows = $disbursementService->buildDailyRows(Carbon::parse($date), $user, $request->daily_seller_id);
        $dayExpenses = Expense::with('expensePayments')->active()
            ->whereNotNull('contract_id')
            ->whereDate('date', $date)
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($request->daily_seller_id, function ($query, $sellerId) {
                return $query->where('seller_id', $sellerId);
            })
            ->get();
        $summary = $disbursementService->summary($rows, $dayExpenses);
        $sellerName = null;

        if ($request->daily_seller_id) {
            $sellerName = optional(User::find($request->daily_seller_id))->name;
        }

        $pdf = Pdf::loadView('expenses.daily_pdf', [
            'rows' => $rows,
            'summary' => $summary,
            'dateLabel' => Carbon::parse($date)->format('d/m/Y'),
            'sellerName' => $sellerName,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Desembolsos_dia_' . Carbon::parse($date)->format('d_m_Y') . '.pdf');
    }

    public function index_cash(Request $request){
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
            })->latest('date')->latest('id')
            ->whereNull('contract_id');

        // calcular total desde expense_payments para los gastos filtrados
        $expenseIds = $expensesQuery->pluck('id')->toArray();
        $total = 0;
        if (!empty($expenseIds)) {
            $total = ExpensePayment::whereIn('expenses_id', $expenseIds)
                ->when($request->payment_method_id, function($query, $payment_method_id){
                    return $query->where('payment_method_id', $payment_method_id);
                })->sum('amount');
        }

        $expenses = $expensesQuery->paginate(20);

        $sellers = User::seller()->where('state', 0)->active()->get();
        $payment_methods = PaymentMethod::all();
        
        return view('expenses.index_cash', compact('expenses', 'sellers', 'payment_methods', 'total'));
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

        if ($request->contract_id && Expense::activeDisbursementExists((int) $request->contract_id)) {
            return response()->json([
                'status' => false,
                'error' => 'Ya existe un desembolso registrado para este préstamo. Edite o elimine el registro existente.',
            ]);
        }

        $image = null;

        if($request->hasFile('image')){
            $image = $request->image->store('expenses', 'public');
        }

        try {
            DB::transaction(function () use ($request, $image, &$expense) {
                if ($request->contract_id) {
                    $exists = Expense::active()
                        ->where('contract_id', (int) $request->contract_id)
                        ->lockForUpdate()
                        ->exists();

                    if ($exists) {
                        throw new \RuntimeException('duplicate_disbursement');
                    }
                }

                $expense = Expense::create([
                    'description' => $request->description,
                    'seller_id' => $request->seller_id,
                    'contract_id' => $request->contract_id,
                    'payment_method_id' => $request->payment_method_id,
                    'date' => $request->date,
                    'image' => $image
                ]);

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
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'duplicate_disbursement') {
                return response()->json([
                    'status' => false,
                    'error' => 'Ya existe un desembolso registrado para este préstamo. Edite o elimine el registro existente.',
                ]);
            }

            throw $e;
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
            'payment_method_id' => $request->payment_method_id,
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

    public function excel_cash(Request $request){
        $name = "Egresos_caja_".now()->format('d_m_Y').".xlsx";
        return Excel::download(new ExpensesCashExport, $name);
    }

}
