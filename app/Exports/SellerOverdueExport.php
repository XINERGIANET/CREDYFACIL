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
        return StandardExcelFormat::fromContract($contract, [
            'days_overdue' => (int) ($contract->days_overdue ?? 0),
            'deuda_total' => (float) ($contract->overdue_debt ?? 0),
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
