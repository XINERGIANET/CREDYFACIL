<?php

namespace App\Exports;

use App\Services\ClientPortfolioService;
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
    protected $service;

    public function __construct(Collection $contracts, $asOfDate)
    {
        $this->contracts = $contracts;
        $this->asOfDate = Carbon::parse($asOfDate);
        $this->service = app(ClientPortfolioService::class);
    }

    public function collection()
    {
        return $this->contracts;
    }

    public function map($contract): array
    {
        $row = $this->service->contractExportRow($contract, $this->asOfDate);

        return [
            $row['origin_seller'],
            $row['current_seller'],
            $row['client'],
            $row['document'],
            $row['phone'],
            $row['address'],
            $row['civil_status'],
            $row['client_type'],
            $row['credit_amount'],
            $row['capital_balance'],
            $row['disbursement_date'],
            $row['total_quotas'],
            $row['paid_quotas'],
            $row['quota_amount'],
            $row['balance_as_of'],
        ];
    }

    public function headings(): array
    {
        return [
            'Asesor de Origen',
            'Asesor Comercial Actual',
            'Cliente',
            'DNI',
            'Teléfono',
            'Dirección',
            'Estado Civil',
            'Tipo de Cliente',
            'Monto del crédito',
            'Saldo capital',
            'Fecha de desembolso',
            'Cuotas totales',
            'Cuotas pagadas',
            'Monto por cuota',
            'Saldo del crédito',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
