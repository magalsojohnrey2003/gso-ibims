<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportsExport implements FromArray, ShouldAutoSize, WithEvents
{
    protected array $columns;
    protected array $rows;
    protected array $meta;
    protected array $exportArray = [];

    public function __construct(array $columns = [], array $rows = [], array $meta = [])
    {
        $this->columns = array_values($columns);
        $this->rows = array_values($rows);
        $this->meta = $meta;
    }

    public function array(): array
    {
        $out = [];

        // Meta section
        if (!empty($this->meta['title'])) $out[] = [$this->meta['title']];
        if (!empty($this->meta['start']) || !empty($this->meta['end'])) {
            $out[] = ['Period', trim(($this->meta['start'] ?? '') . ' to ' . ($this->meta['end'] ?? ''))];
        }
        if (!empty($this->meta['generated_at'])) $out[] = ['Generated', $this->meta['generated_at']];
        if (!empty($this->meta['title']) || !empty($this->meta['start']) || !empty($this->meta['generated_at'])) {
            $out[] = []; // spacer only if meta exists
        }

        // Header row
        $out[] = $this->columns ?: (isset($this->rows[0]) ? array_keys($this->rows[0]) : ['No Data']);

        // Data rows
        foreach ($this->rows as $row) {
            $out[] = array_map(fn($v) => is_scalar($v) ? $v : json_encode($v), (array) $row);
        }

        $this->exportArray = $out;
        return $out;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $totalRows = count($this->exportArray);
                $colsCount = max(1, count($this->columns));
                $lastCol = Coordinate::stringFromColumnIndex($colsCount);

                // Calculate meta rows
                $metaRows = 0;
                if (!empty($this->meta['title'])) $metaRows++;
                if (!empty($this->meta['start']) || !empty($this->meta['end'])) $metaRows++;
                if (!empty($this->meta['generated_at'])) $metaRows++;
                if ($metaRows > 0) $metaRows++; // spacer

                $headerRow = $metaRows + 1;
                $dataStart = $headerRow + 1;

                /** ---------------- STYLING ---------------- **/

                // Title
                if (!empty($this->meta['title'])) {
                    $sheet->mergeCells("A1:{$lastCol}1");
                    $sheet->getStyle('A1')->applyFromArray([
                        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4A148C']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                }

                // Meta info styling (Period, Generated)
                if ($metaRows > 1) {
                    $sheet->getStyle("A2:{$lastCol}{$metaRows}")
                        ->getFont()->setItalic(true)->setSize(10);
                }

                // Header styling
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '6A1B9A'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Borders for full table
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$totalRows}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD'],
                        ],
                    ],
                ]);

                // Alternate row colors
                for ($r = $dataStart; $r <= $totalRows; $r++) {
                    if (($r - $dataStart) % 2 === 0) {
                        $sheet->getStyle("A{$r}:{$lastCol}{$r}")
                            ->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F7FAFC');
                    }
                }

                // Global alignment and wrapping (applied once, not in loop)
                $sheet->getStyle("A1:{$lastCol}{$totalRows}")
                    ->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                // Left align data rows, center header
                $sheet->getStyle("A{$dataStart}:{$lastCol}{$totalRows}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Row height
                for ($r = 1; $r <= $totalRows; $r++) {
                    $sheet->getRowDimension($r)->setRowHeight(20);
                }

                // Freeze header and add filter
                $sheet->freezePane("A" . ($headerRow + 1));
                $sheet->setAutoFilter("A{$headerRow}:{$lastCol}{$totalRows}");
            },
        ];
    }
}
