<?php

namespace App\Services;

use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SellerOverdueService
{
    /**
     * Contratos activos del asesor con al menos una cuota vencida e impaga (deuda > 0).
     */
    public function overdueContractsQuery(int $sellerId): Builder
    {
        $today = now()->toDateString();

        return Contract::active()
            ->where('approved', 1)
            ->where('paid', 0)
            ->where('seller_id', $sellerId)
            ->whereHas('quotas', function ($q) use ($today) {
                $q->where('paid', 0)
                    ->where('debt', '>', 0)
                    ->whereDate('date', '<', $today);
            });
    }

    public function overdueClientsCount(int $sellerId): int
    {
        return (int) $this->overdueContractsQuery($sellerId)
            ->selectRaw("COUNT(DISTINCT CASE
                WHEN client_type = 'Personal' THEN CONCAT('P:', COALESCE(document, ''))
                ELSE CONCAT('G:', COALESCE(group_name, ''))
            END) as total")
            ->value('total');
    }

    public function overdueContractsWithDetails(int $sellerId): Collection
    {
        $contracts = $this->overdueContractsQuery($sellerId)
            ->with(['quotas'])
            ->orderBy('name')
            ->orderBy('group_name')
            ->get();

        return $contracts->map(function (Contract $contract) {
            $oldestOverdueQuota = $contract->quotas
                ->where('paid', 0)
                ->where('debt', '>', 0)
                ->filter(function ($q) {
                    return Carbon::parse($q->date)->lt(now()->startOfDay());
                })
                ->sortBy('date')
                ->first();

            $contract->days_overdue = $oldestOverdueQuota
                ? (int) Carbon::parse($oldestOverdueQuota->date)->diffInDays(now()->startOfDay())
                : 0;

            $contract->overdue_debt = (float) $contract->quotas
                ->where('paid', 0)
                ->where('debt', '>', 0)
                ->filter(function ($q) {
                    return Carbon::parse($q->date)->lt(now()->startOfDay());
                })
                ->sum('debt');

            return $contract;
        });
    }
}
