<?php

namespace App\Exports;

use App\Models\Contract;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

use Maatwebsite\Excel\Concerns\WithEvents;

class SentinelExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Contract::active()->where('approved', 1)->with(['quotas', 'district.province.department'])->get();
    }

    public function map($contract): array
    {
        // Split name into Paterno, Materno, Nombres
        $nameParts = explode(' ', trim($contract->name));
        $paterno = $nameParts[0] ?? '';
        $materno = $nameParts[1] ?? '';
        $nombres = implode(' ', array_slice($nameParts, 2));

        $vencida = $contract->quotas->where('paid', 0)->filter(function ($q) {
            return Carbon::parse($q->date)->lte(now());
        })->sum('debt');

        $maxDelay = 0;
        foreach ($contract->quotas->where('paid', 0) as $quota) {
            $days = Carbon::parse($quota->date)->diffInDays(now(), false);
            if ($days > $maxDelay) $maxDelay = $days;
        }

        // Calificacion based on max delay (standard Peruvian bank rules)
        $rating = 0;
        if ($maxDelay > 120) $rating = 4;
        elseif ($maxDelay > 60) $rating = 3;
        elseif ($maxDelay > 30) $rating = 2;
        elseif ($maxDelay > 8) $rating = 1;

        // Fill array with 35 empty strings to represent columns A to AI
        $row = array_fill(0, 35, '');

        $row[0] = now()->format('Y/m'); // A: Mes de Reporte
        $row[1] = '';             // B: Código Entidad
        $row[2] = $contract->number_pagare; // C: Número del Crédito
        $row[3] = '1';                // D: Tipo Documento
        $row[4] = $contract->document; // E: Nº Documento Identidad
        $row[5] = '';                 // F: Razón Social
        $row[6] = $paterno;           // G: Apellido Paterno
        $row[7] = $materno;           // H: Apellido Materno
        $row[8] = $nombres;           // I: Nombres
        $row[9] = '1';                // J: Tipo Persona
        $row[10] = '5';               // K: Tipo de Crédito

        $row[14] = $vencida > 0 ? number_format($vencida, 1, '.', '') : ''; // O: MN Deuda Directa Vencida > 30

        $row[27] = $rating;           // AB: Calificación SBS
        $row[28] = (int)$maxDelay;    // AC: Número días vencidos o morosos

        return $row;
    }

    public function headings(): array
    {
        return [
            'Mes de Reporte',
            'Código Entidad',
            'Número del Crédito',
            'Tipo Documento Identidad',
            'N° Documento Identidad',
            'Razon Social',
            'Apellido Paterno',
            'Apellido Materno',
            'Nombres',
            'Tipo Persona',
            'Tipo de Crédito',
            'MN Deuda Directa Vigente',
            'MN Deuda Directa Refinanciada',
            'MN Deuda Directa Vencida < 30',
            'MN Deuda Directa Vencida > 30',
            'MN Deuda Directa Cobranza Judicial',
            'MN Deuda Indirecta',
            'MN Deuda Avalada',
            'MN Línea de Crédito',
            'MN Créditos Castigados',
            'ME Deuda Directa Vigente',
            'ME Deuda Directa Refinanciada',
            'ME Deuda Directa Vencida < 30',
            'ME Deuda Directa Vencida > 30',
            'ME Deuda Directa Cobranza Judicial',
            'ME Deuda Indirecta',
            'ME Créditos Castigados',
            'Calificación SBS',
            'Número días vencidos o morosos',
            'Dirección',
            'Distrito',
            'Provincia',
            'Departamento',
            'Teléfono',
            'Estado'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function(\Maatwebsite\Excel\Events\AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // A-K: Magenta
                $sheet->getStyle('A1:K1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('9E036B');

                // L-T: Lighter Magenta/Pink
                $sheet->getStyle('L1:T1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('D97096');

                // U-AA: Even Lighter Pink
                $sheet->getStyle('U1:AA1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('E6B8B7');

                // AB-AC: Magenta
                $sheet->getStyle('AB1:AC1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('9E036B');

                // AD-AH: Dark Blue
                $sheet->getStyle('AD1:AH1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('203764');

                // AI (Estado): Magenta
                $sheet->getStyle('AI1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('9E036B');
            },
        ];
    }
}
