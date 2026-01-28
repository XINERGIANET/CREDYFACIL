<?php

namespace App\Exports;

use App\Models\Income;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class IncomesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Income::with('product', 'store')->latest('date')->get();
    }

    public function map($income): array
    {
        return [
            optional($income->store)->name,
            optional(optional($income->product)->category)->name,
            optional($income->product)->name,
            $income->quantity > 0 ? $income->quantity : '0',
            $income->date->format('d/m/Y H:i:s'),
        ];
    }

    public function headings(): array
    {
        return [
            'Sede',
            'Categoría',
            'Producto',
            'Cantidad',
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
