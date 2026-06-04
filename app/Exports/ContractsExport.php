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
