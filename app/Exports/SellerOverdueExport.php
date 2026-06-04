<?php

namespace App\Exports;

use App\Services\SellerOverdueService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SellerOverdueExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $seller_id;

    public function __construct($seller_id)
    {
        $this->seller_id = $seller_id;
    }

    public function collection()
    {
        return (new SellerOverdueService())->overdueContractsWithDetails($this->seller_id);
    }

    public function map($contract): array
    {
        return [
            $contract->document,
            $contract->client(),
            $contract->overdue_debt ?? 0,
            $contract->days_overdue,
        ];
    }

    public function headings(): array
    {
        return [
            'DNI',
            'Nombre',
            'Deuda en mora',
            'Días de mora',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
