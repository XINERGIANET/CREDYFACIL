<?php

namespace App\Exports;

use App\Models\Contract;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        $user = auth()->user();
        $request = request();

        return Contract::active()
            ->with('seller')
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($request->name, function ($query, $name) {
                return $query->where(function ($query) use ($name) {
                    return $query
                        ->where('name', 'like', '%' . $name . '%')
                        ->orWhere('group_name', 'like', '%' . $name . '%');
                });
            })
            ->when($request->seller_id, function ($query, $seller_id) {
                return $query->where('seller_id', $seller_id);
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

    public function map($contract): array
    {
        $cliente = $contract->client_type == 'Personal' ? $contract->name : $contract->group_name;

        return [
            $cliente,
            optional($contract->seller)->name,
            $contract->requested_amount,
            $contract->quotas_number,
            $contract->interest,
            $contract->payable_amount,
            optional($contract->date)->format('d/m/Y'),
            $contract->paid ? 'Pagado' : 'Pendiente',
            $contract->approved ? 'SÍ' : 'NO',
        ];
    }

    public function headings(): array
    {
        return [
            'Cliente/Grupo',
            'Asesor comercial',
            'Monto solicitado',
            'Cuotas',
            'Interés',
            'Monto a pagar',
            'Fecha de préstamo',
            'Estado',
            'Aprobado',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

