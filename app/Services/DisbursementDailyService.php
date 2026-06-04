<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\DisbursementCheck;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DisbursementDailyService
{
    protected $bcpMethodIds = null;

    public function bcpMethodIds(): array
    {
        if ($this->bcpMethodIds === null) {
            $this->bcpMethodIds = PaymentMethod::whereRaw('UPPER(name) LIKE ?', ['%BCP%'])
                ->pluck('id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->all();
        }

        return $this->bcpMethodIds;
    }

    public function bcpMethodId()
    {
        $ids = $this->bcpMethodIds();

        return $ids[0] ?? null;
    }

    protected function applyBcpPaymentFilter($query)
    {
        return $query->bcp();
    }

    public function dailyContracts(Carbon $date, User $user, $sellerId = null): Collection
    {
        return $this->dailyContractsQuery($date, $user, $sellerId)->get();
    }

    public function dailyContractsQuery(Carbon $date, User $user, $sellerId = null)
    {
        return Contract::active()
            ->where('approved', 1)
            ->whereDate('date', $date)
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($sellerId, function ($query, $sellerId) {
                return $query->where('seller_id', $sellerId);
            })
            ->with([
                'seller',
                'expenses' => function ($query) {
                    $query->active()->with('expensePayments.paymentMethod');
                },
            ])
            ->orderBy('seller_id')
            ->orderBy('name')
            ->orderBy('group_name');
    }

    public function buildDailyRows(Carbon $date, User $user, $sellerId = null): Collection
    {
        $contracts = $this->dailyContracts($date, $user, $sellerId);
        $checkMap = DisbursementCheck::whereDate('date', $date)
            ->whereIn('contract_id', $contracts->pluck('id'))
            ->pluck('marked', 'contract_id');

        return $contracts->map(function (Contract $contract) use ($date, $checkMap) {
            return $this->enrichContract($contract, $date, (bool) ($checkMap[$contract->id] ?? false));
        });
    }

    public function enrichContract(Contract $contract, Carbon $date, bool $marked = false): array
    {
        $retanqueo = $this->bcpRetanqueo($contract, $date);
        $requested = (float) $contract->requested_amount;
        $net = max(0, round($requested - $retanqueo['amount'], 2));
        $expense = $contract->expenses->first();
        $disbursedAmount = 0.0;

        if ($expense) {
            $disbursedAmount = (float) $expense->expensePayments->sum('amount');
        }

        return [
            'id' => $contract->id,
            'client' => $contract->client(),
            'client_type' => $contract->client_type,
            'document' => $contract->document,
            'group_name' => $contract->group_name,
            'seller_id' => $contract->seller_id,
            'seller_name' => optional($contract->seller)->name,
            'contract_type' => $contract->type(),
            'requested_amount' => $requested,
            'bcp_retainage' => $retanqueo['amount'],
            'bcp_payments' => $retanqueo['payments'],
            'net_amount' => $net,
            'disbursed' => $contract->expenses->isNotEmpty(),
            'disbursed_amount' => $disbursedAmount,
            'expense_id' => $expense ? $expense->id : null,
            'pending' => $contract->expenses->isEmpty(),
            'marked' => $marked,
            'contract_date' => $contract->date->format('d/m/Y'),
            'phone' => $contract->phone,
            'address' => $contract->address,
            'quotas_number' => $contract->quotas_number,
            'payable_amount' => (float) $contract->payable_amount,
        ];
    }

    public function bcpRetanqueo(Contract $contract, Carbon $date): array
    {
        if (empty($this->bcpMethodIds())) {
            return ['amount' => 0, 'payments' => []];
        }

        $payments = [];
        $total = 0;

        foreach ($this->previousContracts($contract) as $prev) {
            $lastQuotaNum = (int) $prev->quotas()->max('number');

            $dayPayments = $this->applyBcpPaymentFilter(Payment::active())
                ->with(['quota', 'payment_method'])
                ->whereDate('date', $date->toDateString())
                ->whereHas('quota', function ($query) use ($prev) {
                    $query->where('contract_id', $prev->id);
                })
                ->orderBy('id')
                ->get();

            foreach ($dayPayments as $payment) {
                $quotaNumber = (int) optional($payment->quota)->number;
                $amount = (float) $payment->amount;
                $total += $amount;
                $payments[] = [
                    'payment_id' => $payment->id,
                    'amount' => $amount,
                    'contract_id' => $prev->id,
                    'contract_label' => $prev->client(),
                    'quota_number' => $quotaNumber,
                    'is_last_quota' => $lastQuotaNum > 0 && $quotaNumber === $lastQuotaNum,
                    'date' => $payment->date->format('d/m/Y'),
                ];
            }
        }

        return [
            'amount' => round($total, 2),
            'payments' => $payments,
        ];
    }

    public function dayBcpPayments(Carbon $date, User $user, $sellerId = null): Collection
    {
        if (empty($this->bcpMethodIds())) {
            return collect();
        }

        $dailyContracts = $this->dailyContracts($date, $user, $sellerId);

        if ($dailyContracts->isEmpty()) {
            return collect();
        }

        return $this->applyBcpPaymentFilter(Payment::active())
            ->with(['quota.contract.seller', 'payment_method'])
            ->whereDate('date', $date->toDateString())
            ->whereHas('quota.contract', function ($query) use ($user, $sellerId, $dailyContracts) {
                $query->active()->where('approved', 1)
                    ->when($user->hasRole('seller'), function ($q) use ($user) {
                        return $q->where('seller_id', $user->id);
                    })
                    ->when($sellerId, function ($q, $sellerId) {
                        return $q->where('seller_id', $sellerId);
                    })
                    ->where(function ($q) use ($dailyContracts) {
                        foreach ($dailyContracts as $contract) {
                            $q->orWhere(function ($sub) use ($contract) {
                                if ($contract->document) {
                                    $sub->where('document', $contract->document);
                                } elseif ($contract->group_name) {
                                    $sub->where('group_name', $contract->group_name);
                                } else {
                                    $sub->where('id', $contract->id);
                                }
                            });
                        }
                    });
            })
            ->orderBy('date')
            ->get()
            ->map(function (Payment $payment) {
                $contract = optional(optional($payment->quota)->contract);

                return [
                    'payment_id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'client' => $contract ? $contract->client() : 'N/A',
                    'contract_id' => $contract ? $contract->id : null,
                    'quota_number' => optional($payment->quota)->number,
                    'is_last_quota' => $contract && optional($payment->quota)->number == $contract->quotas()->max('number'),
                    'seller_name' => optional(optional($contract)->seller)->name,
                    'date' => $payment->date->format('d/m/Y'),
                ];
            });
    }

    public function summary(Collection $rows, Collection $dayExpenses): array
    {
        $approvedCount = $rows->count();
        $disbursedCount = $rows->where('disbursed', true)->count();
        $pendingCount = $rows->where('pending', true)->count();
        $markedCount = $rows->where('marked', true)->count();
        $totalRequested = $rows->sum('requested_amount');
        $totalRetainage = $rows->sum('bcp_retainage');
        $totalNet = $rows->sum('net_amount');
        $totalDisbursed = $rows->sum('disbursed_amount');
        $cashOutFromExpenses = (float) $dayExpenses->sum(function ($expense) {
            return $expense->expensePayments->sum('amount');
        });

        return [
            'approved_count' => $approvedCount,
            'disbursed_count' => $disbursedCount,
            'pending_count' => $pendingCount,
            'marked_count' => $markedCount,
            'total_requested' => round($totalRequested, 2),
            'total_retainage' => round($totalRetainage, 2),
            'total_net' => round($totalNet, 2),
            'total_disbursed' => round($totalDisbursed, 2),
            'cash_out_expenses' => round($cashOutFromExpenses, 2),
        ];
    }

    public function contractDetail(Contract $contract, Carbon $date): array
    {
        $contract->load(['seller', 'district', 'expenses.expensePayments.paymentMethod']);
        $row = $this->enrichContract($contract, $date, false);
        $previous = $this->previousContracts($contract)->load('seller');

        return array_merge($row, [
            'people_html' => $contract->people(),
            'district' => optional($contract->district)->name,
            'reference' => $contract->reference,
            'percentage' => $contract->percentage,
            'interest' => (float) $contract->interest,
            'quota_amount' => (float) $contract->quota_amount,
            'previous_contracts' => $previous->map(function (Contract $prev) {
                return [
                    'id' => $prev->id,
                    'client' => $prev->client(),
                    'requested_amount' => (float) $prev->requested_amount,
                    'date' => $prev->date->format('d/m/Y'),
                    'seller_name' => optional($prev->seller)->name,
                ];
            })->values(),
        ]);
    }

    protected function previousContracts(Contract $contract): Collection
    {
        $query = Contract::active()
            ->where('approved', 1)
            ->where('id', '!=', $contract->id);

        if ($contract->document) {
            $query->where('document', $contract->document);
        } elseif ($contract->group_name) {
            $query->where('group_name', $contract->group_name);
        } else {
            return collect();
        }

        return $query->orderByDesc('date')->get();
    }

    protected function clientKey(Contract $contract): string
    {
        return ($contract->document ?: '') . '|' . ($contract->group_name ?: '');
    }
}
