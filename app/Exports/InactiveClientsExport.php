<?php

namespace App\Exports;

use App\Http\Controllers\ClientController;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InactiveClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        return ClientController::inactiveClientsQuery(request())->get();
    }

    public function map($contract): array
    {
        return StandardExcelFormat::fromContract($contract, [
            'days_overdue' => 0,
            'deuda_total' => 0,
        ]);
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
