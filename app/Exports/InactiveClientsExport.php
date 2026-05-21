<?php

namespace App\Exports;

use App\Http\Controllers\ClientController;
use App\Models\Contract;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InactiveClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        return ClientController::inactiveClientsQuery(request())->get();
    }

    public function map($contract): array
    {
        return [
            $contract->client(),
            $this->documentLabel($contract),
            $contract->phone,
            $contract->client_type,
            optional($contract->seller)->name,
            $contract->id,
            $contract->requested_amount,
            $contract->payable_amount,
            optional($contract->date)->format('d/m/Y'),
            optional($contract->last_payment_date)->format('d/m/Y'),
            $this->formatDate($contract->last_payment_date_value),
            $contract->last_payment_amount_value,
            $contract->total_paid_value,
        ];
    }

    private function documentLabel(Contract $contract): string
    {
        if ($contract->client_type === 'Personal') {
            return (string) ($contract->document ?? '');
        }

        $people = $contract->people ? json_decode($contract->people, true) : [];
        if (! is_array($people)) {
            return '';
        }

        return implode(', ', array_filter(array_column($people, 'document')));
    }

    private function formatDate($date): string
    {
        if (! $date) {
            return '';
        }

        return Carbon::parse($date)->format('d/m/Y');
    }

    public function headings(): array
    {
        return [
            'Cliente/Grupo',
            'DNI/Integrantes',
            'Telefono',
            'Tipo',
            'Asesor comercial',
            'Ultimo contrato',
            'Monto solicitado',
            'Monto a pagar',
            'Fecha de prestamo',
            'Fecha de ultima cuota',
            'Ultimo pago',
            'Monto ultimo pago',
            'Total pagado',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
