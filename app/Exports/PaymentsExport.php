<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        $user = auth()->user();
        $request = request();

        return Payment::active()
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->whereHas('quota.contract', function ($query) use ($user) {
                    return $query->where('seller_id', $user->id);
                });
            })
            ->when($request->name, function ($query, $name) {
                return $query->whereHas('quota.contract', function ($query) use ($name) {
                    return $query->where(function ($query) use ($name) {
                        return $query
                            ->where('name', 'like', '%' . $name . '%')
                            ->orWhere('group_name', 'like', '%' . $name . '%');
                    });
                });
            })
            ->when($request->payment_method_id, function ($query, $payment_method_id) {
                return $query->where('payment_method_id', $payment_method_id);
            })
            ->when($request->seller_id, function ($query, $seller_id) {
                return $query->whereHas('quota.contract', function ($query) use ($seller_id) {
                    return $query->where('seller_id', $seller_id);
                });
            })
            ->when($request->start_date, function ($query, $start_date) {
                return $query->whereDate('date', '>=', $start_date);
            })
            ->when($request->end_date, function ($query, $end_date) {
                return $query->whereDate('date', '<=', $end_date);
            })
            ->latest('date')
            ->latest('id')
            ->get();
    }

    public function map($payment): array
    {
        $contract = optional(optional($payment->quota)->contract);

        return [
            $contract->client(),
            optional($contract->seller)->name,
            optional($payment->quota)->number,
            $payment->amount,
            optional($payment->payment_method)->name,
            optional($payment->date)->format('d/m/Y'),
            $payment->due_days,
        ];
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'Asesor comercial',
            'Número de cuota',
            'Monto',
            'Método de pago',
            'Fecha de pago',
            'Días de mora',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

