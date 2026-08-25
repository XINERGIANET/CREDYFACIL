<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PortfolioDailyClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $contracts;
    protected $asOfDate;

    public function __construct(Collection $contracts, $asOfDate)
    {
        $this->contracts = $contracts;
        $this->asOfDate = Carbon::parse($asOfDate);
    }

    public function collection()
    {
        return $this->contracts;
    }

    public function map($contract): array
    {
        return StandardExcelFormat::fromContract($contract, ['as_of_date' => $this->asOfDate]);
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
