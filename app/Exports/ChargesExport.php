<?php

namespace App\Exports;

use App\Models\Quota;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class ChargesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $user = auth()->user();
        $request = request();
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
        })->where('paid', 0)->orderBy('date')->get();

        return $quotas;

    }

    public function map($quota): array
    {
        return [
            optional($quota->contract)->client(),
            $quota->number,
            $quota->amount,
            $quota->debt,
            $quota->date->format('d/m/Y')
        ];
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'Número de cuota',
            'Monto',
            'Saldo',
            'Fecha de pago'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
