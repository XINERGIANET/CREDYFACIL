<?php

namespace App\Exports;

use App\Models\Contract;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ContractsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function collection()
    {
        $user = auth()->user();
        $request = request();

        return Contract::active()
            ->with('seller')
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($request->name, function ($query, $name) {
                return $query->where(function ($query) use ($name) {
                    return $query
                        ->where('name', 'like', '%' . $name . '%')
                        ->orWhere('group_name', 'like', '%' . $name . '%');
                });
            })
            ->when($request->seller_id, function ($query, $seller_id) {
                return $query->where('seller_id', $seller_id);
            })
            ->when($request->start_date, function ($query, $start_date) {
                return $query->whereDate('date', '>=', $start_date);
            })
            ->when($request->end_date, function ($query, $end_date) {
                return $query->whereDate('date', '<=', $end_date);
            })
            ->latest('date')
            ->latest('id')
            ->get();
    }

    private function quotaType(Contract $contract): string
    {
        $map = [1 => 'Semanal', 2 => 'Catorcenal', 4 => 'Mensual'];

        if (!is_null($contract->type_quota) && isset($map[(int) $contract->type_quota])) {
            return $map[(int) $contract->type_quota];
        }

        // Fallback para contratos viejos: calcular por diferencia entre las 2 primeras cuotas
        $firstTwo = $contract->quotas()->orderBy('date')->limit(2)->get();

        if ($firstTwo->count() > 1) {
            $diff = Carbon::parse($firstTwo[0]->date)->diffInDays(Carbon::parse($firstTwo[1]->date));

            if ($diff >= 25 && $diff <= 35) return 'Mensual';
            if ($diff >= 12 && $diff <= 16) return 'Catorcenal';
            if ($diff >= 5  && $diff <= 9)  return 'Semanal';
        }

        return 'No definido';
    }

    public function map($contract): array
    {
        $cliente = $contract->client_type == 'Personal' ? $contract->name : $contract->group_name;

        return [
            $cliente,
            optional($contract->seller)->name,
            $this->quotaType($contract),
            $contract->requested_amount,
            $contract->quotas_number,
            $contract->percentage . '%',
            $contract->interest,
            $contract->payable_amount,
            $contract->insurance_amount,
            optional($contract->date)->format('d/m/Y'),
            $contract->paid ? 'Pagado' : 'Pendiente',
            $contract->approved ? 'SÍ' : 'NO',
        ];
    }

    public function headings(): array
    {
        return [
            'Cliente/Grupo',
            'Asesor comercial',
            'Tipo de cuota',
            'Monto solicitado',
            'Cuotas',
            '% de interés',
            'Interés',
            'Monto a pagar',
            'Monto seguro',
            'Fecha de préstamo',
            'Estado',
            'Aprobado',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
