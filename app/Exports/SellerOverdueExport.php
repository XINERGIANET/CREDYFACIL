<?php

namespace App\Exports;

use App\Models\Contract;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class SellerOverdueExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $seller_id;

    public function __construct($seller_id)
    {
        $this->seller_id = $seller_id;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $contracts = Contract::where('seller_id', $this->seller_id)
            ->whereHas('quotas', function($q){
                $q->where('paid', 0)->whereDate('date', '<', now());
            })->with('quotas')->get();

        $contracts = $contracts->map(function($contract) {
            $oldestOverdueQuota = $contract->quotas
                ->where('paid', 0)
                ->filter(fn($q) => Carbon::parse($q->date)->lt(now()))
                ->sortBy('date')
                ->first();

            $contract->days_overdue = $oldestOverdueQuota
                ? (int) Carbon::parse($oldestOverdueQuota->date)->diffInDays(now())
                : 0;

            return $contract;
        });

        return $contracts;
    }

    public function map($contract): array
    {
        return [
            $contract->document,
            $contract->client(),
            $contract->payable_amount,
            $contract->days_overdue
        ];
    }

    public function headings(): array
    {
        return [
            'DNI',
            'Nombre',
            'Monto',
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
