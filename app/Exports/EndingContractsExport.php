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
    public function collection()
    {
        $request = request();
        $user = auth()->user();

        $start_date = $request->start_date ? $request->start_date : now();
        $end_date = $request->end_date ? $request->end_date : now();

        return Contract::active()->when($user->hasRole('seller'), function ($query) use ($user) {
            return $query->where('seller_id', $user->id);
        })->when($request->name, function ($query, $name) {
            return $query->where(function ($query) use ($name) {
                return $query->where('name', 'like', '%' . $name . '%')->orWhere('group_name', 'like', '%' . $name . '%');
            });
        })->when($request->seller_id, function ($query, $seller_id) {
            return $query->where('seller_id', $seller_id);
        })->where('paid', 0)
            ->where('approved', 1)
            ->whereDate('last_payment_date', '>=', $start_date)
            ->whereDate('last_payment_date', '<=', $end_date)
            ->with('seller')
            ->oldest('last_payment_date')
            ->get();
    }

    public function map($contract): array
    {
        return StandardExcelFormat::fromContract($contract);
    }

    public function headings(): array
    {
        return StandardExcelFormat::headings();
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
