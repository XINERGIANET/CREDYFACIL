<?php

namespace App\Exports;

use App\Models\Contract;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SellerContractsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
        return Contract::where('seller_id', $this->seller_id)->get();
    }

    public function map($contract): array
    {
        return [
            $contract->number_pagare,
            $contract->client(),
            $contract->payable_amount,
            $contract->date
        ];
    }

    public function headings(): array
    {
        return [
            'Número de pagaré',
            'Cliente/Grupo',
            'Monto',
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
