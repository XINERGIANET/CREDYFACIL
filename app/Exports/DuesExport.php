<?php

namespace App\Exports;

use App\Models\Quota;
use App\Services\ClientPortfolioService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DuesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $service;

    public function __construct()
    {
        $this->service = app(ClientPortfolioService::class);
    }

    public function collection()
    {
        return $this->service->groupedOverdueClients($this->overdueQuotas());
    }

    protected function overdueQuotas()
    {
        $user = auth()->user();
        $request = request();
        $date = $request->date ? $request->date : now();

        return Quota::active()->whereHas('contract', function ($query) {
            $query->active()->where('approved', 1)->where('paid', 0);
        })->when($user->hasRole('seller'), function ($query) use ($user) {
            return $query->whereHas('contract', function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            });
        })->when($request->name, function ($query, $name) {
            return $query->whereHas('contract', function ($query) use ($name) {
                return $query->where(function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%')
                        ->orWhere('group_name', 'like', '%' . $name . '%');
                });
            });
        })->when($request->seller_id, function ($query, $seller_id) {
            return $query->whereHas('contract', function ($query) use ($seller_id) {
                return $query->where('seller_id', $seller_id);
            });
        })->when($request->from_days, function ($query, $from_days) {
            return $query->whereRaw('DATEDIFF(?, date) >= ?', [now()->format('Y-m-d'), $from_days]);
        })->when($request->to_days, function ($query, $to_days) {
            return $query->whereRaw('DATEDIFF(?, date) <= ?', [now()->format('Y-m-d'), $to_days]);
        })
            ->where('paid', 0)
            ->where('debt', '>', 0)
            ->whereDate('date', '<', $date)
            ->with(['contract.seller'])
            ->get();
    }

    public function map($row): array
    {
        return StandardExcelFormat::fromGroupedOverdue($row);
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
