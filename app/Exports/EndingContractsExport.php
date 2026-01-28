<?php

namespace App\Exports;

use App\Models\Contract;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class EndingContractsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $request = request();
        $user = auth()->user();
        
        $start_date = $request->start_date ? $request->start_date : now();
        $end_date = $request->end_date ? $request->end_date : now();
        
        $contracts = Contract::active()->when($user->hasRole('seller'), function($query) use($user){
            return $query->where('seller_id', $user->id);
        })->when($request->name, function($query, $name){
            return $query->where(function($query) use ($name){
                return $query->where('name', 'like', '%'.$name.'%')->orWhere('group_name', 'like', '%'.$name.'%');
            });
        })->when($request->seller_id, function($query, $seller_id){
            return $query->where('seller_id', $seller_id);
        })->whereDate('last_payment_date', '>=', $start_date)->whereDate('last_payment_date', '<=', $end_date)
        ->oldest('last_payment_date')->get();

        return $contracts;

    }

    public function map($contract): array
    {
        return [
            $contract->client_type == 'Personal' ? $contract->name : $contract->group_name,
            optional($contract)->seller->name,
            $contract->requested_amount,
            $contract->quotas_number,
            $contract->interest,
            $contract->payable_amount,
            $contract->date->format('d/m/Y'),
            $contract->last_payment_date->format('d/m/Y')
        ];
    }

    public function headings(): array
    {
        return [
            'Cliente/Grupo',
            'Asesor C.',
            'Monto solicitado',
            'Cuotas',
            'Interés',
            'Monto a pagar',
            'Fecha de prestamo',
            'Fecha de última cuota',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
