<?php

namespace App\Exports;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\Quota;
use App\Services\ClientPortfolioService;
use Carbon\Carbon;

class StandardExcelFormat
{
    public static function headings(): array
    {
        return [
            'Asesor de Origen',
            'Asesor Responsable',
            'Cliente',
            'DNI',
            'Direccion',
            'celular',
            'Monto Desembolsado',
            'Monto Total',
            'Fecha de Desembolso',
            'Monto de Cuota',
            'N° de Cuotas',
            'Cuotas Canceladas',
            'Dias de Mora',
            'Deuda Total',
        ];
    }

    public static function fromContract(Contract $contract, array $options = []): array
    {
        $service = app(ClientPortfolioService::class);
        $origin = $service->originSeller($contract);
        $display = $service->currentActiveContract($contract) ?: $contract;

        $daysOverdue = array_key_exists('days_overdue', $options)
            ? (int) $options['days_overdue']
            : self::maxOverdueDays($display);

        if (isset($options['as_of_date'])) {
            $deudaTotal = $service->contractDebtAsOf($display->id, Carbon::parse($options['as_of_date']));
        } elseif (array_key_exists('deuda_total', $options)) {
            $deudaTotal = (float) $options['deuda_total'];
        } else {
            $deudaTotal = $service->totalDebt($display);
        }

        $fechaDesembolso = isset($options['payment_date'])
            ? $options['payment_date']
            : ($display->date ? $display->date->format('d/m/Y') : '');

        $montoCuota = isset($options['payment_amount'])
            ? round((float) $options['payment_amount'], 2)
            : round((float) $display->quota_amount, 2);

        $cuotasCanceladas = isset($options['quota_number'])
            ? (int) $options['quota_number']
            : $service->paidQuotasCount($display);

        return [
            $origin ? $origin->name : optional($display->seller)->name,
            optional($display->seller)->name,
            $display->client(),
            self::documentLabel($display),
            $display->address ?: '',
            $display->phone ?: '',
            round((float) $display->requested_amount, 2),
            round((float) $display->payable_amount, 2),
            $fechaDesembolso,
            $montoCuota,
            (int) $display->quotas_number,
            $cuotasCanceladas,
            $daysOverdue,
            round($deudaTotal, 2),
        ];
    }

    public static function fromGroupedOverdue(object $row): array
    {
        $contract = self::resolveContract($row->contract ?? null);

        if (!$contract) {
            return array_fill(0, 14, '');
        }

        return self::fromContract($contract, [
            'days_overdue' => (int) ($row->days_overdue ?? 0),
            'deuda_total' => (float) ($row->total_overdue_debt ?? 0),
        ]);
    }

    public static function fromPayment(Payment $payment): array
    {
        $payment->loadMissing('quota.contract.seller');
        $contract = $payment->quota ? self::resolveContract($payment->quota->contract) : null;

        if (!$contract) {
            return array_fill(0, 14, '');
        }

        return self::fromContract($contract, [
            'days_overdue' => (int) ($payment->due_days ?? 0),
            'deuda_total' => (float) $payment->amount,
            'payment_amount' => (float) $payment->amount,
            'payment_date' => $payment->date ? $payment->date->format('d/m/Y') : '',
            'quota_number' => $payment->quota ? (int) $payment->quota->number : 0,
        ]);
    }

    public static function fromQuota($quota): array
    {
        $quota->loadMissing('contract.seller');
        $contract = self::resolveContract($quota->contract);

        if (!$contract) {
            return array_fill(0, 14, '');
        }

        $days = $quota->date ? (int) Carbon::parse($quota->date)->diffInDays(now()->startOfDay()) : 0;
        if ($quota->date && Carbon::parse($quota->date)->gte(now()->startOfDay())) {
            $days = 0;
        }

        return self::fromContract($contract, [
            'days_overdue' => $days,
            'deuda_total' => (float) $quota->debt,
        ]);
    }

    public static function fromExpense($expense): array
    {
        $expense->loadMissing(['contract.seller', 'seller', 'expensePayments']);

        if ($expense->contract) {
            return self::fromContract($expense->contract, ['days_overdue' => 0]);
        }

        return [
            '',
            optional($expense->seller)->name,
            $expense->description,
            '',
            '',
            '',
            '',
            '',
            $expense->date ? $expense->date->format('d/m/Y') : '',
            '',
            '',
            '',
            0,
            round((float) $expense->expensePayments->sum('amount'), 2),
        ];
    }

    public static function documentLabel(Contract $contract): string
    {
        if ($contract->client_type === 'Personal') {
            return (string) ($contract->document ?? '');
        }

        $people = $contract->people ? json_decode($contract->people, true) : [];
        if (!is_array($people)) {
            return (string) ($contract->group_name ?? '');
        }

        $docs = array_filter(array_column($people, 'document'));

        return $docs ? implode(', ', $docs) : (string) ($contract->group_name ?? '');
    }

    protected static function resolveContract($value): ?Contract
    {
        return $value instanceof Contract ? $value : null;
    }

    protected static function maxOverdueDays(Contract $contract): int
    {
        $max = 0;

        foreach ($contract->quotas()->where('paid', 0)->where('debt', '>', 0)->whereDate('date', '<', now())->get() as $quota) {
            $days = (int) Carbon::parse($quota->date)->diffInDays(now()->startOfDay());
            if ($days > $max) {
                $max = $days;
            }
        }

        return $max;
    }
}
