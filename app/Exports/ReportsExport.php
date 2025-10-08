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

    /**
     * @param array $columns Indexed array of header names (e.g. ['ID','Name', ...])
     * @param array $rows    Array of indexed arrays - data rows (matching columns order)
     * @param array $meta    ['title'=>..., 'start'=>..., 'end'=>..., 'generated_at'=>...]
     */
    public function __construct(array $columns = [], array $rows = [], array $meta = [])
    {
        $this->columns = array_values($columns);
        $this->rows = array_values($rows);
        $this->meta = $meta;
    }

    /**
     * Build the entire sheet as an array (called by Laravel-Excel).
     * The returned array is the raw row-by-row content placed into the sheet.
     */
    public function array(): array
    {
        $out = [];

        // Title
        if (!empty($this->meta['title'])) {
            $out[] = [$this->meta['title']];
        }

        // Period
        if (!empty($this->meta['start']) || !empty($this->meta['end'])) {
            $period = trim(($this->meta['start'] ?? '') . ' to ' . ($this->meta['end'] ?? ''));
            $out[] = ['Period', $period];
        }

        // Generated timestamp
        if (!empty($this->meta['generated_at'])) {
            $out[] = ['Generated', $this->meta['generated_at']];
        }

        // Blank spacer row
        $out[] = [];

        // Header row (explicit)
        if (!empty($this->columns)) {
            $out[] = $this->columns;
        } else {
            // If no columns provided, create headers from the first row's keys (best effort)
            if (!empty($this->rows) && is_array($this->rows[0])) {
                $out[] = array_keys($this->rows[0]);
            } else {
                $out[] = ['No data'];
            }
        }

        // Data rows
        foreach ($this->rows as $row) {
            if (is_array($row)) {
                $clean = array_map(function ($cell) {
                    if (is_null($cell)) return '';
                    if (is_bool($cell)) return $cell ? '1' : '0';
                    if (is_object($cell)) {
                        if (method_exists($cell, '__toString')) return (string)$cell;
                        return json_encode($cell);
                    }
                    return is_scalar($cell) ? $cell : json_encode($cell);
                }, $row);
                $out[] = $clean;
            } else {
                $out[] = [is_scalar($row) ? $row : json_encode($row)];
            }
        }

        // Save for AfterSheet usage
        $this->exportArray = $out;

        return $out;
    }

    /**
     * Register events for styling & layout after sheet is populated.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $export = $this->exportArray;
                $totalRows = count($export);
                $colsCount = max(1, count($this->columns));
                $lastColLetter = Coordinate::stringFromColumnIndex($colsCount);

                // Determine header row index dynamically:
                // header is the row that matches $this->columns, otherwise fallback to (meta rows + 1)
                $headerRowIndex = null;
                foreach ($export as $i => $row) {
                    if (is_array($row) && $row === $this->columns) {
                        $headerRowIndex = $i + 1; // 1-based
                        break;
                    }
                }

                // Fallback: count meta rows (title, period, generated) + spacer
                if (is_null($headerRowIndex)) {
                    $metaCount = 0;
                    if (!empty($this->meta['title'])) $metaCount++;
                    if (!empty($this->meta['start']) || !empty($this->meta['end'])) $metaCount++;
                    if (!empty($this->meta['generated_at'])) $metaCount++;
                    // spacer row
                    $metaCount++;
                    $headerRowIndex = $metaCount + 1;
                }

                // Title formatting (row 1) if present
                if (!empty($this->meta['title'])) {
                    $sheet->getStyle("A1")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 14],
                    ]);
                }

                // Period / Generated (rows 2-4) make slightly dimmer
                $metaRowsEnd = $headerRowIndex - 1;
                if ($metaRowsEnd >= 2) {
                    $sheet->getStyle("A2:{$lastColLetter}{$metaRowsEnd}")->applyFromArray([
                        'font' => ['italic' => true, 'size' => 10],
                    ]);
                }

                // Header row style: bold + white text on readable dark-blue
                $headerRange = "A{$headerRowIndex}:{$lastColLetter}{$headerRowIndex}";
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F4E79'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Auto-size columns
                // Note: getHighestColumn() returns a letter; we only autosize based on columns count we provided
                for ($col = 1; $col <= $colsCount; $col++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
                }

                // Build full table range from header to lastRow
                $dataStartRow = $headerRowIndex + 1;
                $dataEndRow = $totalRows;
                if ($dataEndRow < $dataStartRow) $dataEndRow = $dataStartRow;

                $fullRange = "A{$headerRowIndex}:{$lastColLetter}{$dataEndRow}";

                // Thin borders for table
                $sheet->getStyle($fullRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'DDDDDD'],
                        ],
                    ],
                ]);

                // Alternating row fill for readability (applies to data rows only)
                for ($r = $dataStartRow; $r <= $dataEndRow; $r++) {
                    if (($r - $dataStartRow) % 2 === 0) {
                        $sheet->getStyle("A{$r}:{$lastColLetter}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)
                              ->getStartColor()->setRGB('F7FAFC'); // light
                    }
                    // ensure vertical center alignment
                    $sheet->getStyle("A{$r}:{$lastColLetter}{$r}")
                          ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                }

                // Freeze header row and enable autofilter from header to end
                $sheet->freezePane("A" . ($headerRowIndex + 1));
                $sheet->setAutoFilter("A{$headerRowIndex}:{$lastColLetter}{$dataEndRow}");

                // Set a minimum row height for spacing
                for ($i = 1; $i <= $dataEndRow; $i++) {
                    $sheet->getRowDimension($i)->setRowHeight(20);
                }
            },
        ];
    }
}
