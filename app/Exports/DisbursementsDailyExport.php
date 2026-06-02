<?php

namespace App\Exports;

use App\Services\DisbursementDailyService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DisbursementsDailyExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $rows;
    protected $summary;
    protected $date;

    public function __construct($rows, array $summary, string $date)
    {
        $this->rows = $rows;
        $this->summary = $summary;
        $this->date = $date;
    }

    public function array(): array
    {
        $data = [
            ['Control de desembolsos - ' . Carbon::parse($this->date)->format('d/m/Y')],
            [],
            ['Resumen', 'Valor'],
            ['Contratos aprobados del día', $this->summary['approved_count']],
            ['Desembolsados', $this->summary['disbursed_count']],
            ['Pendientes', $this->summary['pending_count']],
            ['Monto solicitado total', $this->summary['total_requested']],
            ['Retanqueo BCP total', $this->summary['total_retainage']],
            ['Neto a entregar', $this->summary['total_net']],
            ['Efectivo registrado en egresos', $this->summary['cash_out_expenses']],
            [],
            ['Marcado', 'Cliente', 'Documento/Grupo', 'Asesor', 'Tipo', 'Solicitado', 'BCP ret.', 'Neto', 'Estado', 'Desembolsado'],
        ];

        foreach ($this->rows as $row) {
            $data[] = [
                $row['marked'] ? 'SI' : '',
                $row['client'],
                $row['document'] ?: $row['group_name'],
                $row['seller_name'],
                $row['contract_type'],
                $row['requested_amount'],
                $row['bcp_retainage'],
                $row['net_amount'],
                $row['disbursed'] ? 'Desembolsado' : 'Pendiente',
                $row['disbursed_amount'],
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            3 => ['font' => ['bold' => true]],
            12 => ['font' => ['bold' => true]],
        ];
    }
}
