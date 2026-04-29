<?php

namespace App\Exports;

use App\Models\Contract;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
                return $query->where('name', 'like', '%' . $name . '%');
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
            ->groupBy('document')
            ->groupBy('group_name')
            ->get();
    }

    public function map($contract): array
    {
        $cliente = $contract->client_type == 'Personal' ? $contract->name : $contract->group_name;

        return [
            $cliente,
            $this->documentLabel($contract),
            $this->phoneLabel($contract),
            $this->addressLabel($contract),
            $this->civilStatusLabel($contract),
            $contract->type(),
            optional($contract->seller)->name,
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

        $docs = array_filter(array_column($people, 'document'));

        return implode(', ', $docs);
    }

    private function phoneLabel(Contract $contract): string
    {
        return (string) ($contract->phone ?? '');
    }

    private function addressLabel(Contract $contract): string
    {
        if ($contract->client_type === 'Personal') {
            return (string) ($contract->address ?? '');
        }

        $people = $contract->people ? json_decode($contract->people, true) : [];
        if (! is_array($people)) {
            return (string) ($contract->address ?? '');
        }

        $addrs = array_filter(array_column($people, 'address'));
        if ($addrs !== []) {
            return implode(' | ', $addrs);
        }

        return (string) ($contract->address ?? '');
    }

    private function civilStatusLabel(Contract $contract): string
    {
        if ($contract->client_type === 'Personal') {
            return (string) ($contract->civil_status ?? '');
        }

        return '';
    }

    public function headings(): array
    {
        return [
            'Cliente/Grupo',
            'DNI',
            'Teléfono',
            'Dirección',
            'Estado civil',
            'Tipo de cliente',
            'Asesor comercial',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
