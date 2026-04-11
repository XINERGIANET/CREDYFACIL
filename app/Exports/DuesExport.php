<?php

namespace App\Exports;

use App\Models\Quota;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DuesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $user = auth()->user();
        $request = request();
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
        })->whereDate('date', '<', $date)->where('paid', 0)->get();

        return $quotas;
    }

    public function map($quota): array
    {
        return [
            optional($quota->contract)->client(),
            $quota->number,
            $quota->amount,
            $quota->debt,
            $quota->date->format('d/m/Y'),
            $quota->date->diffInDays(now())
        ];
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'Número de cuota',
            'Monto',
            'Saldo',
            'Fecha de pago',
            'Días de mora'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
