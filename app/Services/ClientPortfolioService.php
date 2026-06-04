<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\Quota;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClientPortfolioService
{
    public function clientKey(Contract $contract): string
    {
        if ($contract->client_type === 'Personal' && $contract->document) {
            return 'P:' . $contract->document;
        }
        if ($contract->group_name) {
            return 'G:' . $contract->group_name;
        }

        return 'C:' . $contract->id;
    }

    public function activeContractsAsOf($sellerId, Carbon $date): Collection
    {
        return Contract::active()
            ->where('approved', 1)
            ->when($sellerId, function ($query, $sellerId) {
                return $query->where('seller_id', $sellerId);
            })
            ->whereDate('date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereHas('quotas', function ($q) {
                    $q->where('debt', '>', 0);
                })->orWhereHas('quotas.payments', function ($q) use ($date) {
                    $q->active()->whereDate('date', '>', $date);
                });
            })
            ->with('seller')
            ->orderByDesc('date')
            ->get()
            ->unique(function ($contract) {
                return $this->clientKey($contract);
            })
            ->values();
    }

    public function clientsAsOfCount($sellerId, Carbon $date): int
    {
        return $this->activeContractsAsOf($sellerId, $date)->count();
    }

    public function newClientContracts($sellerId, Carbon $start, Carbon $end): Collection
    {
        return Contract::active()
            ->where('approved', 1)
            ->when($sellerId, function ($query, $sellerId) {
                return $query->where('seller_id', $sellerId);
            })
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->whereNotExists(function ($query) use ($start) {
                $query->select(DB::raw(1))
                    ->from('contracts as c2')
                    ->where('c2.deleted', 0)
                    ->where('c2.approved', 1)
                    ->whereDate('c2.date', '<', $start)
                    ->where(function ($q) {
                        $q->where(function ($sq) {
                            $sq->whereNotNull('contracts.document')
                                ->whereColumn('c2.document', 'contracts.document');
                        })->orWhere(function ($sq) {
                            $sq->whereNotNull('contracts.group_name')
                                ->whereColumn('c2.group_name', 'contracts.group_name');
                        });
                    });
            })
            ->with('seller')
            ->orderByDesc('date')
            ->get()
            ->unique(function ($contract) {
                return $this->clientKey($contract);
            })
            ->values();
    }

    public function originSeller(Contract $contract): ?User
    {
        $first = $this->clientContractsQuery($contract)
            ->orderBy('date')
            ->orderBy('id')
            ->with('seller')
            ->first();

        return $first ? $first->seller : null;
    }

    public function currentActiveContract(Contract $contract): ?Contract
    {
        return $this->clientContractsQuery($contract)
            ->where('paid', 0)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Nuevo | Recurrente Activo | Inactivo
     */
    public function portfolioClientType(Contract $contract): string
    {
        $active = $this->currentActiveContract($contract);

        if (!$active) {
            return 'Inactivo';
        }

        $priorApproved = $this->clientContractsQuery($contract)
            ->where('approved', 1)
            ->where('id', '!=', $active->id)
            ->whereDate('date', '<', $active->date)
            ->exists();

        if ($priorApproved) {
            return 'Recurrente Activo';
        }

        return 'Nuevo';
    }

    public function contractDebtAsOf(int $contractId, Carbon $date): float
    {
        $currentDebt = (float) Quota::where('contract_id', $contractId)->sum('debt');
        $futurePayments = (float) Payment::active()
            ->whereDate('payments.date', '>', $date)
            ->whereHas('quota', function ($query) use ($contractId) {
                return $query->where('contract_id', $contractId);
            })
            ->sum('amount');

        return round($currentDebt + $futurePayments, 2);
    }

    public function capitalBalance(Contract $contract): float
    {
        return (float) $contract->quotas()->sum('debt');
    }

    public function paidQuotasCount(Contract $contract): int
    {
        return (int) $contract->quotas()->where('paid', 1)->count();
    }

    public function contractExportRow(Contract $contract, ?Carbon $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: today();
        $origin = $this->originSeller($contract);
        $active = $this->currentActiveContract($contract) ?: $contract;
        $display = $active;

        return [
            'origin_seller' => $origin ? $origin->name : optional($contract->seller)->name,
            'current_seller' => optional($display->seller)->name,
            'client' => $display->client(),
            'document' => $display->document ?: '',
            'phone' => $display->phone ?: '',
            'address' => $display->address ?: '',
            'civil_status' => $display->civil_status ?: '',
            'client_type' => $this->portfolioClientType($display),
            'credit_amount' => (float) $display->requested_amount,
            'capital_balance' => $this->capitalBalance($display),
            'disbursement_date' => optional($display->date)->format('d/m/Y'),
            'total_quotas' => (int) $display->quotas_number,
            'paid_quotas' => $this->paidQuotasCount($display),
            'quota_amount' => (float) $display->quota_amount,
            'balance_as_of' => $this->contractDebtAsOf($display->id, $asOfDate),
        ];
    }

    public function clientsListingQuery($user, $request): Builder
    {
        return Contract::active()
            ->where('approved', 1)
            ->when($user->hasRole('seller'), function ($query) use ($user) {
                return $query->where('seller_id', $user->id);
            })
            ->when($request->name, function ($query, $name) {
                return $query->where(function ($query) use ($name) {
                    $query->where('name', 'like', '%' . $name . '%')
                        ->orWhere('group_name', 'like', '%' . $name . '%');
                });
            })
            ->when($request->seller_id, function ($query, $sellerId) {
                return $query->where('seller_id', $sellerId);
            })
            ->when($request->start_date, function ($query, $start) {
                return $query->whereDate('date', '>=', $start);
            })
            ->when($request->end_date, function ($query, $end) {
                return $query->whereDate('date', '<=', $end);
            })
            ->orderByDesc('date')
            ->orderByDesc('id');
    }

    public function dedupeLatestPerClient(Collection $contracts): Collection
    {
        return $contracts->unique(function ($contract) {
            return $this->clientKey($contract);
        })->values();
    }

    public function groupedOverdueClients(Collection $quotas): Collection
    {
        return $quotas->groupBy(function ($quota) {
            $contract = $quota->contract;
            return $contract ? $this->clientKey($contract) : 'Q:' . $quota->id;
        })->map(function ($group) {
            $contract = $this->resolveOverdueDisplayContract($group);
            if (!$contract) {
                return null;
            }

            $overdue = $group->filter(function ($q) use ($contract) {
                return (int) $q->contract_id === (int) $contract->id
                    && (float) $q->debt > 0;
            });

            if ($overdue->isEmpty()) {
                return null;
            }

            $oldest = $overdue->sortBy('date')->first();
            $paidQuotas = $this->paidQuotasCount($contract);
            $totalQuotas = (int) $contract->quotas_number;
            $overdueCount = $overdue->count();

            return (object) [
                'contract' => $contract,
                'client_name' => $contract->client(),
                'document' => $contract->document ?: $contract->group_name,
                'seller_name' => optional($contract->seller)->name,
                'total_overdue_debt' => round((float) $overdue->sum('debt'), 2),
                'requested_amount' => (float) $contract->requested_amount,
                'disbursement_date' => $contract->date ? $contract->date->format('d/m/Y') : '',
                'paid_quotas' => $paidQuotas,
                'total_quotas' => $totalQuotas,
                'quota_amount' => (float) $contract->quota_amount,
                'capital_balance' => $this->capitalBalance($contract),
                'overdue_quotas_count' => $overdueCount,
                'days_overdue' => $oldest ? (int) Carbon::parse($oldest->date)->diffInDays(now()->startOfDay()) : 0,
                'contract_id' => $contract->id,
            ];
        })->filter()->values()->sortByDesc('total_overdue_debt')->values();
    }

    /**
     * Contrato vigente del cliente para mora: préstamo activo (paid=0) más reciente.
     */
    protected function resolveOverdueDisplayContract(Collection $quotaGroup): ?Contract
    {
        $contracts = $quotaGroup->map(function ($quota) {
            return $quota->contract;
        })->filter()->unique('id')->sortByDesc(function ($contract) {
            return $contract->date ? $contract->date->timestamp : 0;
        });

        $active = $contracts->first(function ($contract) {
            return (int) $contract->paid === 0 && (int) $contract->approved === 1;
        });

        if ($active) {
            return $active;
        }

        return $contracts->first();
    }

    protected function clientContractsQuery(Contract $contract): Builder
    {
        $query = Contract::active()->where('approved', 1);

        if ($contract->client_type === 'Personal' && $contract->document) {
            return $query->where('document', $contract->document);
        }
        if ($contract->group_name) {
            return $query->where('group_name', $contract->group_name);
        }

        return $query->where('id', $contract->id);
    }
}
