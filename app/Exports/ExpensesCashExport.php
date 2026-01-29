<?php

namespace App\Exports;

use App\Models\Expense;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class ExpensesCashExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $request = request();
        $user = auth()->user();
        
        $expenses = Expense::with('expensePayments.paymentMethod')->active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->where('seller_id', $user->id);
        })->when($request->description, function($query, $description){
            return $query->where('description', 'like', '%'.$description.'%');
        })->when($request->seller_id, function($query, $seller_id){
            return $query->where('seller_id', $seller_id);
        })->when($request->payment_method_id, function($query, $payment_method_id){
            return $query->whereHas('expensePayments', function($q) use($payment_method_id){
                $q->where('payment_method_id', $payment_method_id);
            });
        })->when($request->start_date, function($query, $start_date){
            return $query->whereDate('date', '>=', $start_date);
        })->when($request->end_date, function($query, $end_date){
            return $query->whereDate('date', '<=', $end_date);
        })->latest('date')->latest('id')->get()
        ->whereNull('contract_id');
        return $expenses;
    }

    public function map($expense): array
    {
        return [
            $expense->description,
            optional($expense->seller)->name,
            // calcular monto total a partir de expensePayments
            $expense->expensePayments->sum('amount'),
            // mostrar metodos de pago (primario / secundario si existe)
            (function($expense){
                $first = $expense->expensePayments->first();
                if(!$first) return '';
                $name = optional($first->paymentMethod)->name;
                if($expense->expensePayments->count() > 1){
                    $second = $expense->expensePayments->get(1);
                    $name .= ' / ' . optional($second->paymentMethod)->name;
                }
                return $name;
            })($expense),
            $expense->date->format('d/m/Y')
        ];
    }

    public function headings(): array
    {
        return [
            'Descripción',
            'Cliente/Grupo',
            'Monto',
            'Método de pago',
            'Fecha'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
