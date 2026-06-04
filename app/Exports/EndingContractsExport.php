<?php

namespace App\Exports;

use App\Models\Contract;
use App\Services\ClientPortfolioService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EndingContractsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $service;

    public function __construct()
    {
        $this->service = app(ClientPortfolioService::class);
    }

    public function collection()
    {
        $request = request();
        $user = auth()->user();

        $start_date = $request->start_date ? $request->start_date : now();
        $end_date = $request->end_date ? $request->end_date : now();

        return Contract::active()->when($user->hasRole('seller'), function ($query) use ($user) {
            return $query->where('seller_id', $user->id);
        })->when($request->name, function ($query, $name) {
            return $query->where(function ($query) use ($name) {
                return $query->where('name', 'like', '%' . $name . '%')->orWhere('group_name', 'like', '%' . $name . '%');
            });
        })->when($request->seller_id, function ($query, $seller_id) {
            return $query->where('seller_id', $seller_id);
        })->where('paid', 0)
            ->where('approved', 1)
            ->whereDate('last_payment_date', '>=', $start_date)
            ->whereDate('last_payment_date', '<=', $end_date)
            ->with('seller')
            ->oldest('last_payment_date')
            ->get();
    }

    public function map($contract): array
    {
        $paid = $this->service->paidQuotasCount($contract);
        $balance = $this->service->capitalBalance($contract);

        return [
            $contract->client_type == 'Personal' ? $contract->name : $contract->group_name,
            optional($contract->seller)->name,
            $contract->requested_amount,
            $contract->payable_amount,
            $contract->quotas_number,
            $paid,
            $contract->date->format('d/m/Y'),
            $contract->quota_amount,
            $balance,
            $contract->last_payment_date->format('d/m/Y'),
            $contract->paid ? 'Pagado' : 'Pendiente',
        ];
    }

    public function headings(): array
    {
        return [
            'Cliente/Grupo',
            'Asesor C.',
            'Monto solicitado',
            'Monto de cartera',
            'Número de cuotas',
            'Cuotas pagadas',
            'Fecha de desembolso',
            'Monto de la cuota',
            'Saldo capital',
            'Fecha de última cuota',
            'Estado',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
