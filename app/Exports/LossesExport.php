<?php

namespace App\Exports;

use App\Models\Loss;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class LossesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Loss::with('product', 'store')->latest('date')->get();
    }

    public function map($loss): array
    {
        return [
            optional($loss->store)->name,
            optional(optional($loss->product)->category)->name,
            optional($loss->product)->name,
            $loss->quantity > 0 ? $loss->quantity : '0',
            $loss->date->format('d/m/Y H:i:s'),
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
