<?php

namespace App\Exports;

use App\Models\Contract;
use App\Services\ClientPortfolioService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $service;

    public function __construct()
    {
        $this->service = app(ClientPortfolioService::class);
    }

    public function collection()
    {
        $user = auth()->user();
        $request = request();

        $contracts = $this->service->clientsListingQuery($user, $request)
            ->with('seller')
            ->get();

        return $this->service->dedupeLatestPerClient($contracts);
    }

    public function map($contract): array
    {
        return StandardExcelFormat::fromContract($contract);
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
