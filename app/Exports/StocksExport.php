<?php

namespace App\Exports;

use App\Models\Stock;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class StocksExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Stock::with('product', 'store')->get();
    }

    public function map($stock): array
    {
        return [
            optional($stock->store)->name,
            optional(optional($stock->product)->category)->name,
            optional($stock->product)->name,
            $stock->stock > 0 ? $stock->stock : '0'
        ];
    }

    public function headings(): array
    {
        return [
            'Sede',
            'Categoría',
            'Producto',
            'Stock'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]]
        ];
    }
}
