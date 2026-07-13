<?php

namespace App\Exports;

use App\Models\Payment;
use App\Models\Quota;
use App\Models\User;
use App\Services\ClientPortfolioService;
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

class PortfolioOverdueReportExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    private $reportDate;

    public function __construct($date = null)
    {
        $this->reportDate = $date ? Carbon::parse($date)->startOfDay() : today();
    }

    public function title(): string
    {
        return 'Cartera Morosa';
    }

    public function array(): array
    {
        $report = $this->data();
        $rows = [
            ['REPORTE DE CARTERA MOROSA CREDYFACIL AL ' . $this->reportDate->format('d/m/Y')],
            $this->headings(),
        ];

        foreach ($report['rows'] as $row) {
            $rows[] = $this->rowValues($row);
        }

        $rows[] = $this->totalValues($report['totals']);

        return $rows;
    }

    private function data(): array
    {
        $rows = [];
        $totals = [
            'wallet' => 0.0,
            'mora_1_7' => 0.0,
            'mora_8_30' => 0.0,
            'mora_gt_7' => 0.0,
            'mora_gt_60' => 0.0,
            'mora_total' => 0.0,
        ];

        $sellers = User::seller()
            ->active()
            ->where('state', 0)
            ->orderBy('name')
            ->get();

        foreach ($sellers as $seller) {
            $wallet = $this->walletValue($seller->id);
            $mora1To7 = $this->overdueRangeValue($seller->id, 'mora_1_7');
            $mora8To30 = $this->overdueRangeValue($seller->id, 'mora_8_30');
            $moraGt7 = $this->walletValue($seller->id, 7);
            $moraGt60 = $this->walletValue($seller->id, 60);
            $moraTotal = $mora1To7 + $moraGt7;

            $row = [
                'seller' => $this->shortSellerName($seller->name),
                'wallet' => $wallet,
                'mora_1_7' => $mora1To7,
                'mora_1_7_percent' => $this->ratio($mora1To7, $wallet),
                'mora_8_30' => $mora8To30,
                'mora_8_30_percent' => $this->ratio($mora8To30, $wallet),
                'mora_gt_7' => $moraGt7,
                'mora_gt_7_percent' => $this->ratio($moraGt7, $wallet),
                'mora_gt_60' => $moraGt60,
                'mora_gt_60_percent' => $this->ratio($moraGt60, $wallet),
                'mora_total' => $moraTotal,
                'mora_total_percent' => $this->ratio($moraTotal, $wallet),
            ];

            $rows[] = $row;

            foreach ($totals as $key => $value) {
                $totals[$key] += $row[$key];
            }
        }

        $totals['mora_1_7_percent'] = $this->ratio($totals['mora_1_7'], $totals['wallet']);
        $totals['mora_8_30_percent'] = $this->ratio($totals['mora_8_30'], $totals['wallet']);
        $totals['mora_gt_7_percent'] = $this->ratio($totals['mora_gt_7'], $totals['wallet']);
        $totals['mora_gt_60_percent'] = $this->ratio($totals['mora_gt_60'], $totals['wallet']);
        $totals['mora_total_percent'] = $this->ratio($totals['mora_total'], $totals['wallet']);

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    private function headings(): array
    {
        return [
            'ASESOR',
            'AVANCE CARTERA',
            "MORA\n1 a 7",
            '%',
            "MORA\n8 a 30",
            '%',
            "MORA\n>7",
            '%',
            'MORA >60',
            '%',
            "MORA\nTOTAL",
            '%',
        ];
    }

    private function rowValues(array $row): array
    {
        return [
            $row['seller'],
            $row['wallet'],
            $row['mora_1_7'],
            $row['mora_1_7_percent'],
            $row['mora_8_30'],
            $row['mora_8_30_percent'],
            $row['mora_gt_7'],
            $row['mora_gt_7_percent'],
            $row['mora_gt_60'],
            $row['mora_gt_60_percent'],
            $row['mora_total'],
            $row['mora_total_percent'],
        ];
    }

    private function totalValues(array $totals): array
    {
        return $this->rowValues(array_merge($totals, ['seller' => 'CREDYFACIL']));
    }

    private function walletValue($sellerId, ?int $overdueDays = null): float
    {
        return $this->quotaDebtValue($sellerId, $overdueDays) + $this->futurePaymentValue($sellerId, $overdueDays);
    }

    private function overdueRangeValue($sellerId, string $metric): float
    {
        return (float) $this->overdueQuotasByMetric($sellerId, $metric)->sum('debt');
    }

    private function overdueQuotasByMetric($sellerId, string $metric)
    {
        $quotas = $this->baseOverdueQuotasQuery($sellerId)
            ->with('contract')
            ->orderBy('date')
            ->get()
            ->map(function ($quota) {
                $quota->days_overdue = optional($quota->date)->diffInDays($this->reportDate);
                return $quota;
            });

        $clientMaxDays = $quotas
            ->groupBy(function ($quota) {
                return app(ClientPortfolioService::class)->clientKey($quota->contract);
            })
            ->map(function ($group) {
                return (int) $group->max('days_overdue');
            });

        return $quotas->filter(function ($quota) use ($metric, $clientMaxDays) {
            $clientKey = app(ClientPortfolioService::class)->clientKey($quota->contract);
            $maxDays = (int) $clientMaxDays->get($clientKey, 0);
            $days = (int) $quota->days_overdue;

            switch ($metric) {
                case 'mora_1_7':
                    return $maxDays >= 1 && $maxDays <= 7 && $days >= 1 && $days <= 7;
                case 'mora_8_30':
                    return $maxDays >= 8 && $maxDays <= 30 && $days >= 8 && $days <= 30;
                case 'mora_gt_7':
                    return $maxDays > 7 && $days > 7;
                case 'mora_gt_60':
                    return $maxDays > 60 && $days > 60;
                case 'mora_total':
                default:
                    return $maxDays >= 1 && $days >= 1;
            }
        })->values();
    }

    private function baseOverdueQuotasQuery($sellerId)
    {
        return Quota::active()
            ->whereHas('contract', function ($query) use ($sellerId) {
                $query->where('approved', 1)
                    ->where('seller_id', $sellerId)
                    ->whereDate('date', '<=', $this->reportDate);
            })
            ->where('paid', 0)
            ->whereDate('date', '<', $this->reportDate);
    }

    private function quotaDebtValue($sellerId, ?int $overdueDays = null): float
    {
        return (float) Quota::whereHas('contract', function ($query) use ($sellerId) {
                $query->active()
                    ->where('approved', 1)
                    ->where('seller_id', $sellerId)
                    ->whereDate('date', '<=', $this->reportDate);
            })
            ->where('paid', 0)
            ->when($overdueDays, function ($query, $days) {
                return $query->whereDate('date', '<', $this->reportDate->copy()->subDays($days));
            })
            ->sum('debt');
    }

    private function futurePaymentValue($sellerId, ?int $overdueDays = null): float
    {
        return (float) Payment::active()
            ->whereDate('payments.date', '>', $this->reportDate)
            ->whereHas('quota.contract', function ($query) use ($sellerId) {
                $query->active()
                    ->where('approved', 1)
                    ->where('seller_id', $sellerId)
                    ->whereDate('date', '<=', $this->reportDate);
            })
            ->when($overdueDays, function ($query, $days) {
                return $query->whereHas('quota', function ($q) use ($days) {
                    $q->whereDate('date', '<', $this->reportDate->copy()->subDays($days));
                });
            })
            ->sum('amount');
    }

    private function ratio($value, $total): float
    {
        return $total > 0 ? $value / $total : 0;
    }

    private function shortSellerName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));

        return strtoupper($parts[0] ?? $name);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $lastColumn = Coordinate::stringFromColumnIndex(12);

                $sheet->mergeCells('A1:' . $lastColumn . '1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00E5E5');

                $sheet->getStyle('A2:' . $lastColumn . '2')->getFont()->setBold(true)->setSize(8);
                $sheet->getStyle('A2:' . $lastColumn . '2')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getStyle('A2:' . $lastColumn . '2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00E5E5');

                $sheet->getStyle('B3:B' . $lastRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');

                foreach (['C', 'E', 'G', 'I', 'K'] as $column) {
                    $sheet->getStyle($column . '3:' . $column . ($lastRow - 1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('808080');
                    $sheet->getStyle($column . '3:' . $column . ($lastRow - 1))->getFont()->getColor()->setARGB('FFFFFF');
                }

                foreach (['D', 'F', 'H', 'J', 'L'] as $column) {
                    $sheet->getStyle($column . '3:' . $column . ($lastRow - 1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFC7CE');
                    $sheet->getStyle($column . '3:' . $column . ($lastRow - 1))->getFont()->getColor()->setARGB('C00000');
                }

                $sheet->getStyle('A' . $lastRow . ':' . $lastColumn . $lastRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('00F000');
                $sheet->getStyle('A' . $lastRow . ':' . $lastColumn . $lastRow)->getFont()->setBold(true);

                $sheet->getStyle('A1:' . $lastColumn . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A3:' . $lastColumn . $lastRow)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A3:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                foreach (['B', 'C', 'E', 'G', 'I', 'K'] as $column) {
                    $sheet->getStyle($column . '3:' . $column . $lastRow)->getNumberFormat()->setFormatCode('"S/" #,##0.0');
                }

                foreach (['D', 'F', 'H', 'J', 'L'] as $column) {
                    $sheet->getStyle($column . '3:' . $column . $lastRow)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_PERCENTAGE_00);
                }

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(34);
                $sheet->freezePane('B3');
            },
        ];
    }
}
