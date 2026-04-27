<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TransactionsExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function __construct(
        private Collection $rows,
        private array $cols = ['agent' => false, 'payment' => false, 'by' => false]
    ) {}

    public function title(): string { return 'Transactions'; }

    public function headings(): array
    {
        $h = ['#', 'Date', 'Customer', 'Description'];
        if ($this->cols['agent'])   $h[] = 'Agent';
        if ($this->cols['payment']) $h[] = 'Payment Mode';
        $h[] = 'Type';
        $h[] = 'Credit';
        $h[] = 'Debit';
        if ($this->cols['by'])      $h[] = 'Created By';
        return $h;
    }

    public function collection(): Collection
    {
        return $this->rows->map(fn($t, $i) => $this->row($t, $i));
    }

    private function row($t, int $i): array
    {
        $divisor = scale_divisor();

        $r = [
            $i + 1,
            $t->transaction_date,
            $t->customer?->customer_name ?? '—',
            $t->description ?? '—',
        ];
        if ($this->cols['agent'])   $r[] = $t->agent?->name ?? '—';
        if ($this->cols['payment']) $r[] = $t->paymentType?->payment_type ?? '—';
        $r[] = $t->type;
        $r[] = $t->credit > 0 ? round($t->credit / $divisor, 2) : '';
        $r[] = $t->debit  > 0 ? round($t->debit  / $divisor, 2) : '';
        if ($this->cols['by'])      $r[] = $t->createdBy?->name ?? 'Import';
        return $r;
    }

    public function columnWidths(): array
    {
        $letters = range('A', 'Z');
        $widths  = [6, 14, 28, 36];
        if ($this->cols['agent'])   $widths[] = 20;
        if ($this->cols['payment']) $widths[] = 18;
        $widths[] = 10; // Type
        $widths[] = 14; // Credit
        $widths[] = 14; // Debit
        if ($this->cols['by'])      $widths[] = 18;

        $result = [];
        foreach ($widths as $i => $w) {
            $result[$letters[$i]] = $w;
        }
        return $result;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B5BDB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
