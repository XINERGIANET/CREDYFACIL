<?php

namespace App\Exports;

use App\Models\Contract;
use App\Models\Payment;
use App\Models\Quota;
use App\Models\User;
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

    private $goals = [
        12 => ['clients' => 3,  'new' => 4,  'disbursement' => 90000],
        13 => ['clients' => 5,  'new' => 6,  'disbursement' => 80000],
        15 => ['clients' => 10, 'new' => 12, 'disbursement' => 35000],
        16 => ['clients' => 12, 'new' => 10, 'disbursement' => 65000],
        17 => ['clients' => 15, 'new' => 16, 'disbursement' => 30000],
        19 => ['clients' => 12, 'new' => 12, 'disbursement' => 5000],
    ];

    private $goalNames = [
        'JACQUELINE' => ['clients' => 3,  'new' => 4,  'disbursement' => 90000],
        'JHON'       => ['clients' => 5,  'new' => 6,  'disbursement' => 80000],
        'MATIAS'     => ['clients' => 10, 'new' => 12, 'disbursement' => 35000],
        'KATHERINE'  => ['clients' => 12, 'new' => 10, 'disbursement' => 65000],
        'YESENIA'    => ['clients' => 15, 'new' => 16, 'disbursement' => 30000],
        'JESSICA'    => ['clients' => 12, 'new' => 12, 'disbursement' => 5000],
    ];

    public function __construct($date = null)
    {
        $this->reportDate = $date ? Carbon::parse($date)->startOfDay() : today();
    }

    public function title(): string
    {
        return 'Reporte Cartera';
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
            '',
            "INIC. MES N°\nCLIENTES",
            "AVANCE N°\nCLIENT. AL DIA",
            "CRECIMIENTO\nN° CLIENTES",
            "META DE\nCLIENTES",
            '%',
            'NUEVOS',
            "META DE\nNUEVOS",
            '%',
            "INIC. MES\nCARTERA",
            'AVANCE CARTERA',
            'CREC. CARTERA',
            'MORA >7',
            "DESEMBOLSO\nMES PASADO",
            "N° OPER.\nMES PASADO",
            'AVANCE DE DESEMBOLSOS',
            "N° DE\nOPER.",
            "META\nMES",
            "AVANCE\nDESEMBOLSOS %",
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

        return $this->quotaDebtAsOf($sellerId, $date, function ($query) use ($limit) {
            $query->whereDate('quotas.date', '<', $limit);
        });
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
        if (isset($this->goals[$seller->id])) {
            return $this->goals[$seller->id];
        }

        $name = strtoupper($seller->name);

        foreach ($this->goalNames as $needle => $goals) {
            if (strpos($name, $needle) !== false) {
                return $goals;
            }
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
        foreach ([1, 2, 3, 4, 6, 7, 9, 10, 11, 13, 14, 15, 16, 17] as $index) {
            $totals[$index] += (float) $row[$index];
        }
    }

    private function totalRow(array $totals): array
    {
        return [
            'CREDYFACIL',
            $totals[1],
            $totals[2],
            $totals[3],
            $totals[4],
            $this->percent($totals[3], $totals[4]),
            $totals[6],
            $totals[7],
            $this->percent($totals[6], $totals[7]),
            $totals[9],
            $totals[10],
            $totals[11],
            $this->percent($this->totalOverdueDebt(), $totals[10]),
            $totals[13],
            $totals[14],
            $totals[15],
            $totals[16],
            $totals[17],
            $this->percent($totals[15], $totals[17]),
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
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00E5E5');

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
                $sheet->getStyle('M2:M' . $lastRow)->getFont()->setBold(true);

                $sheet->getStyle('A' . $lastRow . ':' . $lastColumn . $lastRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00F000');
                $sheet->getStyle('A' . $lastRow . ':' . $lastColumn . $lastRow)->getFont()->setBold(true);

                $sheet->getStyle('A1:' . $lastColumn . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A3:' . $lastColumn . $lastRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle('J3:L' . $lastRow)->getNumberFormat()->setFormatCode('"S/" #,##0.0');
                $sheet->getStyle('N3:N' . $lastRow)->getNumberFormat()->setFormatCode('"S/" #,##0.0');
                $sheet->getStyle('P3:P' . $lastRow)->getNumberFormat()->setFormatCode('"S/" #,##0');
                $sheet->getStyle('R3:R' . $lastRow)->getNumberFormat()->setFormatCode('"S/" #,##0');
                $sheet->getStyle('F3:F' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                $sheet->getStyle('I3:I' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                $sheet->getStyle('M3:M' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                $sheet->getStyle('S3:S' . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getRowDimension(2)->setRowHeight(36);
                $sheet->freezePane('B3');
            },
        ];
    }
}
