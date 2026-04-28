<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class BalanceSummaryExport implements FromArray, WithEvents, WithTitle, WithColumnWidths
{
    public function __construct(
        private Collection $customers,
        private string $filter,
        private bool $scaleOn,
    ) {}

    public function title(): string { return 'Balance Summary'; }

    public function array(): array { return []; }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 10,
            'C' => 30,
            'D' => 18,
            'E' => 14,
            'F' => 14,
            'G' => 14,
            'H' => 16,
            'I' => 10,
            'J' => 16,
            'K' => 16,
            'L' => 16,
            'M' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $e) => $this->buildSheet($e->sheet->getDelegate()),
        ];
    }

    private function buildSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $customers = $this->customers;
        $scaleOn   = $this->scaleOn;
        $lastCol   = 'M';

        $scaleVal = fn (float $v): float => $scaleOn ? round($v / 100, 2) : round($v, 2);

        $row = 1;

        // ── Title ─────────────────────────────────────────────────
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->setCellValue("A{$row}", 'Aman Traders — Balance Summary Report');
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row++;

        // ── Generated on ──────────────────────────────────────────
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->setCellValue("A{$row}", 'Generated on: ' . now()->format('d M Y, h:i A'));
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font'      => ['size' => 10, 'italic' => true, 'color' => ['rgb' => '374151']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF0F7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
        $row++;

        // ── Filter + count ────────────────────────────────────────
        $filterLabel = match ($this->filter) {
            'debit'  => 'Dr — To Collect',
            'credit' => 'Cr — To Pay',
            'zero'   => 'Settled (Zero)',
            default  => 'All Customers',
        };
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->setCellValue("A{$row}", "Filter: {$filterLabel}    |    Total Customers: {$customers->count()}");
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font'      => ['size' => 10, 'color' => ['rgb' => '374151']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF0F7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(18);
        $row++;

        // ── Scale note ────────────────────────────────────────────
        if ($scaleOn) {
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->setCellValue("A{$row}", 'Note: Amounts divided by 100 (Scale Amount Display is ON)');
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font'      => ['size' => 9, 'italic' => true, 'color' => ['rgb' => 'B45309']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(16);
            $row++;
        }

        // Blank spacer
        $sheet->getRowDimension($row)->setRowHeight(6);
        $row++;

        // ── Column headers ────────────────────────────────────────
        $headerRow = $row;
        $amtSuffix = $scaleOn ? ' (÷100)' : '';

        $headers = [
            'A' => 'Sr#',
            'B' => 'Cust. ID',
            'C' => 'Customer Name',
            'D' => 'City',
            'E' => 'State',
            'F' => 'Mobile',
            'G' => 'Phone',
            'H' => 'Opening Bal.' . $amtSuffix,
            'I' => 'Op. Type',
            'J' => 'Total Credit' . $amtSuffix,
            'K' => 'Total Debit' . $amtSuffix,
            'L' => 'Net Balance' . $amtSuffix,
            'M' => 'Direction',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}{$row}", $label);
        }
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B5BDB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '2A4ABF']]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        // ── Data rows ─────────────────────────────────────────────
        $grandCredit = 0.0;
        $grandDebit  = 0.0;
        $grandNet    = 0.0;

        foreach ($customers as $i => $c) {
            $bal = (float) $c->net_balance;
            $dir = $bal > 0.01 ? 'Dr - To Collect' : ($bal < -0.01 ? 'Cr - To Pay' : 'Settled');

            // Row background: light red = Dr, light green = Cr, near-white = settled
            $rowBg = $bal > 0.01 ? 'FEE2E2' : ($bal < -0.01 ? 'D1FAE5' : 'F9FAFB');

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $c->id);
            $sheet->setCellValue("C{$row}", $c->customer_name);
            $sheet->setCellValue("D{$row}", $c->city   ?? '');
            $sheet->setCellValue("E{$row}", $c->state  ?? '');
            $sheet->setCellValue("F{$row}", $c->mobile ?? '');
            $sheet->setCellValue("G{$row}", $c->phone  ?? '');
            $sheet->setCellValue("H{$row}", $scaleVal((float) $c->opening_balance));
            $sheet->setCellValue("I{$row}", $c->opening_balance_type);
            $sheet->setCellValue("J{$row}", $scaleVal((float) $c->total_credit));
            $sheet->setCellValue("K{$row}", $scaleVal((float) $c->total_debit));
            $sheet->setCellValue("L{$row}", $scaleVal(abs($bal)));
            $sheet->setCellValue("M{$row}", $dir);

            // Row base style
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowBg]],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
                'font'    => ['size' => 9],
            ]);

            // Center: Sr#, Cust ID, Op Type, Direction
            foreach (['A', 'B', 'I', 'M'] as $col) {
                $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Right-align + number format: amounts
            foreach (['H', 'J', 'K', 'L'] as $col) {
                $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            }

            // Color Direction cell text
            $dirColor = $bal > 0.01 ? 'DC2626' : ($bal < -0.01 ? '059669' : '6B7280');
            $sheet->getStyle("M{$row}")->getFont()->setBold(true)->getColor()->setRGB($dirColor);

            $grandCredit += $c->total_credit;
            $grandDebit  += $c->total_debit;
            $grandNet    += $bal;

            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;
        }

        // Blank row before totals
        $sheet->getRowDimension($row)->setRowHeight(6);
        $row++;

        // ── Totals row ────────────────────────────────────────────
        $totalsDir      = $grandNet > 0.01 ? 'Dr - To Collect' : ($grandNet < -0.01 ? 'Cr - To Pay' : 'Settled');
        $totalsDirColor = $grandNet > 0.01 ? 'DC2626' : ($grandNet < -0.01 ? '059669' : '6B7280');

        $sheet->mergeCells("A{$row}:I{$row}");
        $sheet->setCellValue("A{$row}", 'GRAND TOTAL (' . $customers->count() . ' customers)');
        $sheet->setCellValue("J{$row}", $scaleVal($grandCredit));
        $sheet->setCellValue("K{$row}", $scaleVal($grandDebit));
        $sheet->setCellValue("L{$row}", $scaleVal(abs($grandNet)));
        $sheet->setCellValue("M{$row}", $totalsDir);

        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F2937']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'F59E0B']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        foreach (['J', 'K', 'L'] as $col) {
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
        }
        $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("M{$row}")->getFont()->setBold(true)->getColor()->setRGB($totalsDirColor);
        $sheet->getRowDimension($row)->setRowHeight(24);

        // ── Freeze pane below header ──────────────────────────────
        $sheet->freezePane('A' . ($headerRow + 1));

        // ── Outer border around table ─────────────────────────────
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$row}")->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '3B5BDB']]],
        ]);
    }
}
