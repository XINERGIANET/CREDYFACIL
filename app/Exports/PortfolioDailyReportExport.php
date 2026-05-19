<?php

namespace App\Exports;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\Quota;
use App\Models\User;
use App\Models\Goal;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PortfolioDailyReportExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    private $reportDate;
    private $cachedGoals;

    public function __construct($date = null)
    {
        $this->reportDate = $date ? Carbon::parse($date)->startOfDay() : today();
    }

    public function title(): string
    {
        return 'Reporte Cartera';
    }

    public function data(): array
    {
        $sellers = User::seller()
            ->active()
            ->where('state', 0)
            ->orderBy('name')
            ->get();

        $dataRows = [];
        $totals = $this->emptyTotals();

        foreach ($sellers as $seller) {
            $row = $this->sellerRow($seller);
            $dataRows[] = [
                'seller_id' => $seller->id,
                'data' => $row
            ];
            $this->addTotals($totals, $row);
        }

        return [
            'rows' => $dataRows,
            'totals' => $this->totalRow($totals)
        ];
    }

    public function array(): array
    {
        $rows = [
            ['REPORTE DE CARTERA CREDYFACIL AL ' . $this->reportDate->format('d/m/Y')],
            $this->headings(),
        ];

        $sellers = User::seller()
            ->active()
            ->where('state', 0)
            ->orderBy('name')
            ->get();

        $totals = $this->emptyTotals();

        foreach ($sellers as $seller) {
            $row = $this->sellerRow($seller);
            $rows[] = $row;
            $this->addTotals($totals, $row);
        }

        $rows[] = $this->totalRow($totals);

        return $rows;
    }

    private function headings(): array
    {
        return [
            'ASESOR',
            "INIC. MES\nN° CLIENTES",
            "AVANCE N°\nCLIENT. AL DIA",
            "CRECIM.\nN° CLIENTES",
            "META DE\nCLIENTES",
            '%',
            'NUEVOS',
            "META DE\nNUEVOS",
            '%',
            "INIC. MES\nCARTERA",
            "AVANCE\nCARTERA",
            "CREC.\nCARTERA",
            'MORA >7',
            "DESEMB.\nMES PASADO",
            "N° OPER.\nMES PASADO",
            "AVANCE\nDESEMB.",
            "N° DE\nOPER.",
            "META\nMES",
            "AVANCE %",
        ];
    }
    private function sellerRow(User $seller): array
    {
        $monthStart = $this->reportDate->copy()->startOfMonth();
        $previousStart = $this->reportDate->copy()->subMonthNoOverflow()->startOfMonth();
        $previousEnd = $this->reportDate->copy()->subMonthNoOverflow()->endOfMonth();
        $goals = $this->sellerGoals($seller);

        $initialClients = $this->clientsAsOf($seller->id, $monthStart->copy()->subDay());
        $currentClients = $this->clientsAsOf($seller->id, $this->reportDate);
        $clientGrowth = $currentClients - $initialClients;
        $newClients = $this->newClients($seller->id, $monthStart, $this->reportDate);

        $initialWallet = $this->walletAsOf($seller->id, $monthStart->copy()->subDay());
        $currentWallet = $this->walletAsOf($seller->id, $this->reportDate);
        $walletGrowth = $currentWallet - $initialWallet;
        $overdueDebt = $this->overdueDebtAsOf($seller->id, $this->reportDate, 7);

        $previousDisbursement = $this->disbursement($seller->id, $previousStart, $previousEnd);
        $previousOperations = $this->operations($seller->id, $previousStart, $previousEnd);
        $currentDisbursement = $this->disbursement($seller->id, $monthStart, $this->reportDate);
        $currentOperations = $this->operations($seller->id, $monthStart, $this->reportDate);

        return [
            $this->shortName($seller->name),
            $initialClients,
            $currentClients,
            $clientGrowth,
            $goals['clients'],
            $this->percent($clientGrowth, $goals['clients']),
            $newClients,
            $goals['new'],
            $this->percent($newClients, $goals['new']),
            $initialWallet,
            $currentWallet,
            $walletGrowth,
            $this->percent($overdueDebt, $currentWallet),
            $previousDisbursement,
            $previousOperations,
            $currentDisbursement,
            $currentOperations,
            $goals['disbursement'],
            $this->percent($currentDisbursement, $goals['disbursement']),
        ];
    }

    private function clientsAsOf($sellerId, Carbon $date): int
    {
        return Contract::active()
            ->where('approved', 1)
            ->where('seller_id', $sellerId)
            ->whereDate('date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereHas('quotas', function ($q) {
                    $q->where('debt', '>', 0);
                })->orWhereHas('quotas.payments', function ($q) use ($date) {
                    $q->active()->whereDate('date', '>', $date);
                });
            })
            ->selectRaw("COUNT(DISTINCT CONCAT(COALESCE(document,''),'|',COALESCE(group_name,''))) as total")
            ->value('total') ?? 0;
    }

    private function newClients($sellerId, Carbon $start, Carbon $end): int
    {
        return Contract::active()
            ->where('approved', 1)
            ->where('seller_id', $sellerId)
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->whereNotExists(function ($query) use ($start) {
                $query->select(\DB::raw(1))
                    ->from('contracts as c2')
                    ->where('c2.deleted', 0)
                    ->where('c2.approved', 1)
                    ->whereDate('c2.date', '<', $start)
                    ->where(function ($q) {
                        $q->where(function($sq){
                            $sq->whereNotNull('contracts.document')
                               ->whereColumn('c2.document', 'contracts.document');
                        })->orWhere(function($sq){
                            $sq->whereNotNull('contracts.group_name')
                               ->whereColumn('c2.group_name', 'contracts.group_name');
                        });
                    });
            })
            ->selectRaw("COUNT(DISTINCT CONCAT(COALESCE(document,''),'|',COALESCE(group_name,''))) as total")
            ->value('total') ?? 0;
    }

    private function walletAsOf($sellerId, Carbon $date): float
    {
        return $this->quotaDebtAsOf($sellerId, $date);
    }

    private function overdueDebtAsOf($sellerId, Carbon $date, int $days): float
    {
        $limit = $date->copy()->subDays($days);

        return (float) Quota::whereHas('contract', function ($query) use ($sellerId, $date) {
                $query->active()
                    ->where('approved', 1)
                    ->where('seller_id', $sellerId)
                    ->whereDate('date', '<=', $date);
            })
            ->where('paid', 0)
            ->whereDate('date', '<', $limit)
            ->sum('debt');
    }

    private function quotaDebtAsOf($sellerId, Carbon $date, $quotaFilter = null): float
    {
        $quotaQuery = Quota::whereHas('contract', function ($query) use ($sellerId, $date) {
            $query->active()
                ->where('approved', 1)
                ->where('seller_id', $sellerId)
                ->whereDate('date', '<=', $date);
        });

        if ($quotaFilter) {
            $quotaFilter($quotaQuery);
        }

        $currentDebt = (float) $quotaQuery->sum('debt');

        $paymentQuery = Payment::active()
            ->whereDate('payments.date', '>', $date)
            ->whereHas('quota.contract', function ($query) use ($sellerId, $date) {
                $query->active()
                    ->where('approved', 1)
                    ->where('seller_id', $sellerId)
                    ->whereDate('date', '<=', $date);
            });

        if ($quotaFilter) {
            $paymentQuery->whereHas('quota', $quotaFilter);
        }

        return $currentDebt + (float) $paymentQuery->sum('amount');
    }

    private function disbursement($sellerId, Carbon $start, Carbon $end): float
    {
        return (float) Contract::active()
            ->where('approved', 1)
            ->where('seller_id', $sellerId)
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->sum('requested_amount');
    }

    private function operations($sellerId, Carbon $start, Carbon $end): int
    {
        return Contract::active()
            ->where('approved', 1)
            ->where('seller_id', $sellerId)
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->count();
    }

    private function percent($value, $target): ?float
    {
        return $target > 0 ? $value / $target : null;
    }

    private function sellerGoals(User $seller): array
    {
        if (!$this->cachedGoals) {
            $this->cachedGoals = Goal::where('month', $this->reportDate->month)
                ->where('year', $this->reportDate->year)
                ->get()
                ->keyBy('seller_id');
        }

        $goal = $this->cachedGoals->get($seller->id);

        if ($goal) {
            return [
                'clients' => $goal->clients,
                'new' => $goal->new_clients,
                'disbursement' => $goal->disbursement
            ];
        }

        return ['clients' => 0, 'new' => 0, 'disbursement' => 0];
    }

    private function shortName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));

        return strtoupper($parts[0] ?? $name);
    }

    private function emptyTotals(): array
    {
        return array_fill(0, 19, 0);
    }

    private function addTotals(array &$totals, array $row): void
    {
        foreach ([1, 2, 3, 4, 6, 7, 9, 10, 11, 13, 14, 15, 16] as $index) {
            $totals[$index] += (float) $row[$index];
        }
    }

    private function totalRow(array $totals): array
    {
        return [
            'CREDYFACIL',
            $totals[1],                                             // INIC. MES CLIENTES
            $totals[2],                                             // AVANCE CLIENTES
            $totals[3],                                             // CRECIMIENTO
            $totals[4],                                             // NUEVOS
            $totals[5],                                             // META CLIENTES
            $this->percent($totals[4], $totals[5]),                 // % clientes
            $totals[7],                                             // META NUEVOS
            $this->percent($totals[4], $totals[7]),                 // % nuevos
            $totals[9],                                             // INIC. MES CARTERA
            $totals[10],                                            // AVANCE CARTERA
            $totals[11],                                            // CREC. CARTERA
            $this->percent($this->totalOverdueDebt(), $totals[10]), // MORA >7
            $totals[13],                                            // DESEMB. MES PASADO
            $totals[14],                                            // NÂ° OPER. MES PASADO
            $totals[15],                                            // AVANCE DESEMBOLSOS
            $totals[16],                                            // NÂ° OPER.
            $totals[17],                                            // META MES
            $this->percent($totals[15], $totals[17]),               // AVANCE DESEMB. %
        ];
    }

    private function totalOverdueDebt(): float
    {
        $sellerIds = User::seller()->active()->where('state', 0)->pluck('id');

        $total = 0;
        foreach ($sellerIds as $sellerId) {
            $total += $this->overdueDebtAsOf($sellerId, $this->reportDate, 7);
        }

        return $total;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = Coordinate::stringFromColumnIndex(19);

                $sheet->mergeCells('A1:' . $lastColumn . '1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('00E5E5');
                $sheet->getStyle('A1')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('152230');

                $sheet->getStyle('A2:' . $lastColumn . '2')->getFont()->setBold(true)->setSize(8);
                $sheet->getStyle('A2:' . $lastColumn . '2')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getStyle('A2:' . $lastColumn . '2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00E5E5');

                foreach (['E', 'H', 'R'] as $column) {
                    $sheet->getStyle($column . '2:' . $column . $lastRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');
                }

                foreach (['F', 'I', 'S'] as $column) {
                    $sheet->getStyle($column . '2:' . $column . $lastRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00F000');
                }

                foreach (['B', 'G', 'J', 'N', 'O'] as $column) {
                    $sheet->getStyle($column . '3:' . $column . ($lastRow - 1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('808080');
                    $sheet->getStyle($column . '3:' . $column . ($lastRow - 1))->getFont()->getColor()->setARGB('FFFFFF');
                }

                $sheet->getStyle('M2:M' . $lastRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0000');
                $sheet->getStyle('M2:M' . $lastRow)->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');

                $sheet->getStyle('A' . $lastRow . ':' . $lastColumn . $lastRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00F000');
                $sheet->getStyle('A' . $lastRow . ':' . $lastColumn . $lastRow)->getFont()->setBold(true);

                $sheet->getStyle('A1:' . $lastColumn . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A3:' . $lastColumn . $lastRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle('J3:L' . $lastRow)->getNumberFormat()->setFormatCode('"S/" #,##0.0');                      // INIC.MES, AVANCE, CREC. CARTERA
                $sheet->getStyle('N3:N' . $lastRow)->getNumberFormat()->setFormatCode('"S/" #,##0.0');                      // DESEMB. MES PASADO
                $sheet->getStyle('P3:P' . $lastRow)->getNumberFormat()->setFormatCode('"S/" #,##0');                        // AVANCE DESEMBOLSOS
                $sheet->getStyle('R3:R' . $lastRow)->getNumberFormat()->setFormatCode('"S/" #,##0');                        // META MES
                $sheet->getStyle('O3:O' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');                             // NÂ° OPER. MES PASADO
                $sheet->getStyle('Q3:Q' . $lastRow)->getNumberFormat()->setFormatCode('#,##0');                             // NÂ° OPER.
                $sheet->getStyle('F3:F' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);  // % clientes
                $sheet->getStyle('I3:I' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);  // % nuevos
                $sheet->getStyle('M3:M' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);  // MORA >7
                $sheet->getStyle('S3:S' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);  // AVANCE DESEMB. %

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(36);
                $sheet->freezePane('B3');
            },
        ];
    }
}
