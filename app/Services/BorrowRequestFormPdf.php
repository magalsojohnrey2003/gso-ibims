<?php

namespace App\Services;

use App\Models\BorrowRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfString;
use setasign\Fpdi\PdfParser\Type\PdfType;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Throwable;

class BorrowRequestFormPdf
{
    /**
     * Cached AcroForm field layout keyed by identifier.
     *
     * @var array<string, array{llx: float, lly: float, urx: float, ury: float}>|null
     */
    private ?array $fieldLayout = null;

    private string $templatePath;

    private string $originalTemplatePath;

    private ?string $preparedTemplatePath = null;

    private bool $repairAttempted = false;

    public function __construct(?string $templatePath = null)
    {
        $configuredTemplate = $templatePath ?? config('borrow-form.template', public_path('pdf/borrow_requests_form.pdf'));
        $this->originalTemplatePath = $configuredTemplate;

        $preparedPath = config('borrow-form.prepared_path');
        if (is_string($preparedPath) && $preparedPath !== '') {
            $this->preparedTemplatePath = $preparedPath;
        }

        if ($this->preparedTemplatePath && is_file($this->preparedTemplatePath)) {
            $this->templatePath = $this->preparedTemplatePath;
        } else {
            $this->templatePath = $configuredTemplate;
        }
    }

    /**
     * Render the populated borrow request form.
     *
     * @return array{filename: string, content: string}
     */
    public function render(BorrowRequest $borrowRequest, ?string $filename = null): array
    {
        if (! class_exists(Fpdi::class)) {
            throw new RuntimeException('The setasign/fpdi package is required to render the borrow request form.');
        }

        if (! is_file($this->templatePath)) {
            if ($this->templatePath !== $this->originalTemplatePath && is_file($this->originalTemplatePath)) {
                $this->templatePath = $this->originalTemplatePath;
            } else {
                throw new RuntimeException("Borrow request form template not found at {$this->templatePath}.");
            }
        }

        $borrowRequest->loadMissing(['user', 'items.item']);

        $layout = $this->getFieldLayout();
        if ($layout === []) {
            throw new RuntimeException(
                'No AcroForm fields were detected in the borrow request form template. ' .
                'If you recently edited the PDF, ensure the form fields remain enabled or configure BORROW_FORM_QPDF_PATH ' .
                'so the system can auto-prepare the template.'
            );
        }

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pageCount = $pdf->setSourceFile($this->templatePath);
        if ($pageCount < 1) {
            throw new RuntimeException('The borrow request form template does not contain any pages.');
        }

        $templateId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($templateId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

        $this->writeText($pdf, $size, Arr::get($layout, 'form_roa'), $borrowRequest->purpose_office ?? '');
        $this->writeText($pdf, $size, Arr::get($layout, 'form_cn'), $borrowRequest->user?->phone ?? '');
        $address = $borrowRequest->location ?? $borrowRequest->user?->address ?? '';
        $this->writeText($pdf, $size, Arr::get($layout, 'form_address'), $address);
        $this->writeText($pdf, $size, Arr::get($layout, 'form_purpose'), $borrowRequest->purpose ?? '');
        $this->writeText($pdf, $size, Arr::get($layout, 'form_db'), $this->formatDate($borrowRequest->borrow_date));
        $this->writeText($pdf, $size, Arr::get($layout, 'form_dtr'), $this->formatUsageAndReturn($borrowRequest));
        $this->writeText($pdf, $size, Arr::get($layout, 'form_tou'), $this->formatUsageRange($borrowRequest->time_of_usage ?? ''));
        $this->writeText($pdf, $size, Arr::get($layout, 'form_name'), $borrowRequest->user?->full_name ?? $borrowRequest->user?->name ?? '');

        // v2: If the 12-slot item fields exist, fill them; otherwise fall back to table (v1)
        if ($this->hasItemSlots($layout)) {
            $this->renderItemSlots($pdf, $size, $layout, $borrowRequest);
        } else {
            $this->renderRequestDetailsTable($pdf, $size, Arr::get($layout, 'form_rd'), $borrowRequest);
        }
        $qrRect = Arr::get($layout, 'form_qr_code') ?? Arr::get($layout, 'form_qr_code_af_image');
        $this->renderQrCode($pdf, $size, $qrRect, $borrowRequest);

        $binary = $pdf->Output('S');

        return [
            'filename' => $filename ? $this->sanitizeFilename($filename) : 'borrow-request-' . $borrowRequest->id . '.pdf',
            'content' => $binary,
        ];
    }

    private function hasItemSlots(array $layout): bool
    {
        // Detect the presence of at least item_1 and check_1 in the AcroForm fields
        return isset($layout['item_1']) || isset($layout['check_1']);
    }

    private function renderItemSlots(Fpdi $pdf, array $pageSize, array $layout, BorrowRequest $borrowRequest): void
    {
        $borrowRequest->loadMissing(['items.item']);

        [$physicalItems, , $manpowerTotal] = $this->splitManpowerItems($borrowRequest);

        // Build selected items first
        $selected = [];
        foreach ($physicalItems as $reqItem) {
            $name = $reqItem->item->name ?? ('Item #' . $reqItem->item_id);
            $qty = (int) ($reqItem->quantity ?? 0);
            $selected[] = [
                'label' => $name . '(x' . $qty . ')',
                'checked' => true,
            ];
        }

        if ($manpowerTotal > 0) {
            $selected[] = [
                'label' => 'Manpower (x' . $manpowerTotal . ')',
                'checked' => true,
            ];
        }

        // Fill remaining slots with other items (random order), unchecked
        try {
            $selectedNames = array_map(function ($row) { return strtolower((string) $row['label']); }, $selected);
            $othersQuery = \App\Models\Item::query()->excludeSystemPlaceholder();
            if (!empty($selectedNames)) {
                $othersQuery->whereNotIn('name', $borrowRequest->items->pluck('item.name')->filter()->values());
            }
            $otherItems = $othersQuery->inRandomOrder()->limit(20)->get(['name'])->pluck('name')->all();
        } catch (\Throwable) {
            $otherItems = [];
        }

        foreach ($otherItems as $nm) {
            if (count($selected) >= 12) break;
            $selected[] = [
                'label' => (string) $nm,
                'checked' => false,
            ];
        }

        // Ensure exactly 12 slots (pad with blanks if needed)
        while (count($selected) < 12) {
            $selected[] = [ 'label' => '', 'checked' => false ];
        }

        // Write into item_1..item_12 and mark check_1..check_12
        for ($i = 1; $i <= 12; $i++) {
            $itemKey = 'item_' . $i;
            $checkKey = 'check_' . $i;
            $data = $selected[$i - 1];

            // Item label text
            $this->writeText($pdf, $pageSize, Arr::get($layout, $itemKey), $data['label'] ?? '');

            // Checkbox visual mark (draw a check mark if selected)
            if (!empty($data['checked'])) {
                $rect = Arr::get($layout, $checkKey);
                if ($rect) {
                    $this->drawCheckboxMark($pdf, $pageSize, $rect);
                }
            }
        }
    }

    private function splitManpowerItems(BorrowRequest $borrowRequest): array
    {
        $items = $borrowRequest->items ?? collect();

        [$manpower, $physical] = $items->partition(function ($item) {
            return (bool) ($item->is_manpower ?? false);
        });

        $manpowerTotal = (int) $manpower->sum(function ($item) {
            return (int) ($item->quantity ?? 0);
        });

        return [$physical->values(), $manpower->values(), $manpowerTotal];
    }

    private function drawCheckboxMark(Fpdi $pdf, array $pageSize, array $rect): void
    {
        // Match Walk-in PDF style: use ZapfDingbats glyph "4" as check mark
        $width = max($rect['urx'] - $rect['llx'], 8);
        $height = max($rect['ury'] - $rect['lly'], 8);
        $box = min($width, $height);
        $x = $rect['llx'] + (($width - $box) / 2);
        $y = ($pageSize['height'] - $rect['ury']) + (($height - $box) / 2);

        $pdf->SetTextColor(55, 65, 81); // gray-700 similar tone
        $pdf->SetFont('ZapfDingbats', '', $box);
        $pdf->Text($x, $y + $box, '4');
    }

    private function writeText(Fpdi $pdf, array $pageSize, ?array $rect, ?string $value, array $options = []): void
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '' || $rect === null) {
            return;
        }

        $paddingX = $options['padding_x'] ?? 4.0;
        $paddingY = $options['padding_y'] ?? 3.0;
        $width = max($rect['urx'] - $rect['llx'] - ($paddingX * 2), 4);
        $height = max($rect['ury'] - $rect['lly'] - ($paddingY * 2), 10);

        $x = $rect['llx'] + $paddingX;
        $y = ($pageSize['height'] - $rect['ury']) + $paddingY;

        $fontSize = $this->resolveFontSize($width, $height, $text, $options['min_font'] ?? 8.5, $options['max_font'] ?? 12.0);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Helvetica', '', $fontSize);
        $pdf->SetXY($x, $y);

        $lineHeight = min(
            max($height / max(ceil(mb_strlen($text, 'UTF-8') / 40), 1), 12),
            18
        );
        $pdf->MultiCell($width, $lineHeight, $this->encode($text), 0, $options['align'] ?? 'L');
    }

    private function renderRequestDetailsTable(Fpdi $pdf, array $pageSize, ?array $rect, BorrowRequest $borrowRequest): void
    {
        if ($rect === null) {
            return;
        }

        [$physicalItems, , $manpowerTotal] = $this->splitManpowerItems($borrowRequest);

        $items = $physicalItems->map(function ($item) use ($borrowRequest) {
            return [
                'id' => (string) $borrowRequest->id,
                'name' => $item->item->name ?? ('Item #' . $item->item_id),
                'quantity' => (string) ($item->quantity ?? 0),
                'manpower' => (string) ($item->assigned_manpower ?? $borrowRequest->manpower_count ?? 0),
            ];
        })->values();

        if ($manpowerTotal > 0) {
            $items->push([
                'id' => (string) $borrowRequest->id,
                'name' => 'Manpower (x' . $manpowerTotal . ')',
                'quantity' => (string) $manpowerTotal,
                'manpower' => (string) $manpowerTotal,
            ]);
        }

        if ($items->isEmpty()) {
            $items = collect([[
                'id' => (string) $borrowRequest->id,
                'name' => 'No items recorded',
                'quantity' => '-',
                'manpower' => '-',
            ]]);
        }

        $x = $rect['llx'] + 2;
        $y = ($pageSize['height'] - $rect['ury']) + 2;
        $width = max($rect['urx'] - $rect['llx'] - 4, 80);
        $height = max($rect['ury'] - $rect['lly'] - 4, 80);

        $headers = [
            'id' => 'Request ID',
            'name' => 'Item Name',
            'quantity' => 'Quantity',
            'manpower' => 'Manpower',
        ];

        $columnWidths = [
            'id' => round($width * 0.18, 2),
            'name' => round($width * 0.46, 2),
            'quantity' => round($width * 0.16, 2),
            'manpower' => round($width * 0.20, 2),
        ];

        $totalAssigned = array_sum($columnWidths);
        if ($totalAssigned !== $width) {
            $columnWidths['manpower'] += $width - $totalAssigned;
        }

        $headerHeight = min(max($height * 0.12, 18), 28);
        $bodyHeight = $height - $headerHeight;
        $rowHeight = min(max($bodyHeight / max($items->count(), 1), 18), 28);

        $pdf->SetLineWidth(0.6);

        // Header
        $pdf->SetFillColor(67, 56, 202);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 10);

        $cursorX = $x;
        foreach ($headers as $key => $label) {
            $widthForCell = $columnWidths[$key];
            $pdf->Rect($cursorX, $y, $widthForCell, $headerHeight, 'FD');
            $pdf->SetXY($cursorX + 4, $y + ($headerHeight / 2) - 5);
            $align = in_array($key, ['quantity', 'manpower'], true) ? 'C' : 'L';
            $pdf->Cell($widthForCell - 8, 10, $this->encode($label), 0, 0, $align);
            $cursorX += $widthForCell;
        }

        // Rows
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(17, 24, 39);
        $currentY = $y + $headerHeight;

        foreach ($items as $index => $row) {
            if ($currentY + $rowHeight > $y + $headerHeight + $bodyHeight) {
                $pdf->SetFont('Helvetica', 'I', 9);
                $pdf->SetTextColor(148, 163, 184);
                $pdf->SetXY($x, $y + $headerHeight + $bodyHeight - 12);
                $pdf->Cell($width, 10, $this->encode('Additional rows not shown (insufficient space).'), 0, 0, 'L');
                break;
            }

            $cursorX = $x;
            $fill = $index % 2 === 0 ? [248, 250, 252] : null;

            foreach ($headers as $key => $_label) {
                $widthForCell = $columnWidths[$key];
                $text = $this->fitText($pdf, (string) $row[$key], $widthForCell - 8);
                $align = in_array($key, ['quantity', 'manpower'], true) ? 'C' : 'L';

                if ($fill) {
                    $pdf->SetFillColor($fill[0], $fill[1], $fill[2]);
                    $pdf->Rect($cursorX, $currentY, $widthForCell, $rowHeight, 'FD');
                } else {
                    $pdf->Rect($cursorX, $currentY, $widthForCell, $rowHeight, 'D');
                }

                $pdf->SetXY($cursorX + 4, $currentY + ($rowHeight / 2) - 5);
                $pdf->Cell($widthForCell - 8, 10, $this->encode($text), 0, 0, $align);

                $cursorX += $widthForCell;
            }

            $currentY += $rowHeight;
        }
    }

    private function renderQrCode(Fpdi $pdf, array $pageSize, ?array $rect, BorrowRequest $borrowRequest): void
    {
        if ($rect === null) {
            return;
        }

        $payload = $this->buildQrPayload($borrowRequest);
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_Q,
            'scale' => 6,
            'imageBase64' => false,
            'addQuietzone' => true,
            'dataModeOverride' => 'byte',
        ]);

        $binary = (new QRCode($options))->render($payload);

        if (! is_string($binary) || $binary === '') {
            return;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'qr');
        if ($tmp === false) {
            return;
        }

        try {
            file_put_contents($tmp, $binary);
            $width = max($rect['urx'] - $rect['llx'], 24);
            $height = max($rect['ury'] - $rect['lly'], 24);
            $x = $rect['llx'];
            $y = $pageSize['height'] - $rect['ury'];

            $pdf->Image($tmp, $x, $y, $width, $height, 'PNG');
        } finally {
            @unlink($tmp);
        }
    }

    private function formatUsageAndReturn(BorrowRequest $borrowRequest): string
    {
        return $this->formatDate($borrowRequest->return_date);
    }

    private function formatUsageRange(?string $range): string
    {
        $raw = trim((string) $range);
        if ($raw === '') {
            return '';
        }

        if (! str_contains($raw, '-')) {
            return $raw;
        }

        [$start, $end] = array_map('trim', explode('-', $raw, 2));
        $startFormatted = $this->formatUsageTime($start);
        $endFormatted = $this->formatUsageTime($end);

        if ($startFormatted === '' && $endFormatted === '') {
            return $raw;
        }

        if ($startFormatted === '') {
            return $endFormatted;
        }

        if ($endFormatted === '') {
            return $startFormatted;
        }

        return "{$startFormatted} - {$endFormatted}";
    }

    private function formatUsageTime(?string $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        try {
            return Carbon::createFromFormat('H:i', $raw)->format('g:i A');
        } catch (Throwable) {
            try {
                return Carbon::parse($raw)->format('g:i A');
            } catch (Throwable) {
                return $raw;
            }
        }
    }

    private function formatDate(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->translatedFormat('F j, Y');
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->translatedFormat('F j, Y');
        }

        if (is_string($value) && $value !== '') {
            try {
                return Carbon::parse($value)->translatedFormat('F j, Y');
            } catch (Throwable) {
                return $value;
            }
        }

        return '';
    }

    private function fitText(Fpdi $pdf, string $text, float $maxWidth): string
    {
        $encoded = $this->encode($text);
        if ($pdf->GetStringWidth($encoded) <= $maxWidth) {
            return $text;
        }

        $ellipsis = '…';
        $trimmed = '';
        $length = mb_strlen($text, 'UTF-8');
        $targetWidth = $maxWidth - $pdf->GetStringWidth($ellipsis);

        for ($i = 0; $i < $length; $i++) {
            $candidate = $trimmed . mb_substr($text, $i, 1, 'UTF-8');
            if ($pdf->GetStringWidth($this->encode($candidate)) > $targetWidth) {
                break;
            }
            $trimmed = $candidate;
        }

        return rtrim($trimmed) . $ellipsis;
    }

    private function resolveFontSize(float $width, float $height, string $value, float $min, float $max): float
    {
        $length = max(mb_strlen(trim($value), 'UTF-8'), 1);
        $estimated = $width / ($length * 0.5);

        return max(min($estimated, $max), $min, min($height, $max));
    }

    private function encode(string $value): string
    {
        $encoded = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);
        if ($encoded === false) {
            return preg_replace('/[^\x20-\x7E]/', '', $value) ?? $value;
        }

        return $encoded;
    }

    /**
     * @return array<string, array{llx: float, lly: float, urx: float, ury: float}>
     */
    private function getFieldLayout(): array
    {
        if ($this->fieldLayout !== null) {
            return $this->fieldLayout;
        }

        $layout = $this->parseFieldLayout($this->templatePath);

        if ($layout === [] && ! $this->repairAttempted) {
            $this->repairAttempted = true;
            if ($this->attemptAutoRepairTemplate()) {
                $layout = $this->parseFieldLayout($this->templatePath);
            }
        }

        $this->fieldLayout = $layout;

        return $this->fieldLayout;
    }

    /**
     * @return array<string, array{llx: float, lly: float, urx: float, ury: float}>
     */
    private function parseFieldLayout(string $path): array
    {
        $layout = [];

        try {
            $parser = new PdfParser(StreamReader::createByFile($path));
            $catalog = PdfDictionary::ensure($parser->getCatalog());

            if (! isset($catalog->value['AcroForm'])) {
                return $layout;
            }

            $acroForm = PdfDictionary::ensure(
                PdfType::resolve($catalog->value['AcroForm'], $parser)
            );

            if (! isset($acroForm->value['Fields'])) {
                return $layout;
            }

            $fields = PdfArray::ensure(
                PdfType::resolve($acroForm->value['Fields'], $parser)
            )->value;

            foreach ($fields as $fieldRef) {
                $this->collectFieldLayout($parser, $fieldRef, $layout);
            }
        } catch (Throwable $e) {
            Log::warning('BorrowRequestFormPdf: unable to parse template fields', [
                'template' => $path,
                'message' => $e->getMessage(),
            ]);
        }

        return $layout;
    }

    private function collectFieldLayout(PdfParser $parser, mixed $fieldObject, array &$layout, string $parentName = ''): void
    {
        try {
            $field = PdfDictionary::ensure(PdfType::resolve($fieldObject, $parser));
        } catch (Throwable) {
            return;
        }

        $values = $field->value;
        $name = $parentName;

        if (isset($values['T'])) {
            $resolvedName = PdfType::resolve($values['T'], $parser);
            if ($resolvedName instanceof PdfString || $resolvedName instanceof PdfName) {
                $rawName = (string) $resolvedName->value;
                $name = $parentName !== '' ? "{$parentName}.{$rawName}" : $rawName;
            }
        }

        if (isset($values['Kids'])) {
            $kids = PdfArray::ensure(PdfType::resolve($values['Kids'], $parser))->value;
            foreach ($kids as $kid) {
                $this->collectFieldLayout($parser, $kid, $layout, $name);
            }
            return;
        }

        if ($name === '' || ! isset($values['Rect'])) {
            return;
        }

        try {
            $rect = PdfArray::ensure(PdfType::resolve($values['Rect'], $parser))->value;
        } catch (Throwable) {
            return;
        }

        $resolved = $this->resolveRect($parser, $rect);
        if ($resolved === null) {
            return;
        }

        $current = $layout[$name] ?? null;
        if ($current !== null) {
            $currentArea = ($current['urx'] - $current['llx']) * ($current['ury'] - $current['lly']);
            $newArea = ($resolved['urx'] - $resolved['llx']) * ($resolved['ury'] - $resolved['lly']);

            if ($newArea < $currentArea) {
                return;
            }
        }

        $layout[$name] = $resolved;
    }

    /**
     * @param array<int, mixed> $items
     * @return array{llx: float, lly: float, urx: float, ury: float}|null
     */
    private function resolveRect(PdfParser $parser, array $items): ?array
    {
        if (count($items) !== 4) {
            return null;
        }

        $resolved = [];
        foreach ($items as $item) {
            $value = PdfType::resolve($item, $parser);
            if ($value instanceof PdfNumeric) {
                $resolved[] = (float) $value->value;
                continue;
            }
            if ($value instanceof PdfString) {
                $resolved[] = (float) $value->value;
                continue;
            }

            return null;
        }

        if (count($resolved) !== 4) {
            return null;
        }

        return [
            'llx' => (float) $resolved[0],
            'lly' => (float) $resolved[1],
            'urx' => (float) $resolved[2],
            'ury' => (float) $resolved[3],
        ];
    }

    private function attemptAutoRepairTemplate(): bool
    {
        $qpdf = $this->findQpdfBinary();
        if (! $qpdf) {
            return false;
        }

        if (! is_file($this->originalTemplatePath)) {
            return false;
        }

        $needsPreparedCopy = $this->preparedTemplatePath !== null;
        $target = $needsPreparedCopy
            ? $this->preparedTemplatePath
            : $this->originalTemplatePath . '.prepared.pdf';

        if ($needsPreparedCopy) {
            $directory = dirname($target);
            if (! is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
        }

        if ($target === $this->originalTemplatePath) {
            $target = $this->originalTemplatePath . '.prepared.pdf';
        }

        $result = $this->executeCommand([
            $qpdf,
            '--qdf',
            '--object-streams=disable',
            $this->originalTemplatePath,
            $target,
        ]);

        if ($result['exit_code'] !== 0) {
            Log::warning('BorrowRequestFormPdf: qpdf failed to prepare template', [
                'exit_code' => $result['exit_code'],
                'stderr' => $result['stderr'],
            ]);

            if (! $needsPreparedCopy && is_file($target)) {
                @unlink($target);
            }

            return false;
        }

        if ($needsPreparedCopy) {
            $this->templatePath = $this->preparedTemplatePath;
        } else {
            if (! @rename($target, $this->originalTemplatePath)) {
                @copy($target, $this->originalTemplatePath);
                @unlink($target);
            }
            $this->templatePath = $this->originalTemplatePath;
        }

        $this->fieldLayout = null;

        Log::info('BorrowRequestFormPdf: prepared borrow form template using qpdf.', [
            'template' => $this->templatePath,
            'qpdf' => $qpdf,
        ]);

        return true;
    }

    private function findQpdfBinary(): ?string
    {
        $configured = config('borrow-form.qpdf_path');
        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = PHP_OS_FAMILY === 'Windows'
            ? [['where', 'qpdf.exe'], ['where', 'qpdf']]
            : [['which', 'qpdf']];

        foreach ($candidates as $command) {
            $result = $this->executeCommand($command);
            if ($result['exit_code'] === 0 && $result['stdout'] !== '') {
                $path = trim(strtok($result['stdout'], "\r\n"));
                if ($path !== '' && is_file($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, string>|string $command
     * @return array{exit_code:int, stdout:string, stderr:string}
     */
    private function executeCommand(array|string $command): array
    {
        if (is_array($command)) {
            $command = implode(' ', array_map('escapeshellarg', $command));
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptorSpec, $pipes, base_path());

        if (! is_resource($process)) {
            return ['exit_code' => 1, 'stdout' => '', 'stderr' => 'Unable to spawn process.'];
        }

        if (isset($pipes[0])) {
            fclose($pipes[0]);
        }

        $stdout = isset($pipes[1]) ? stream_get_contents($pipes[1]) ?: '' : '';
        $stderr = isset($pipes[2]) ? stream_get_contents($pipes[2]) ?: '' : '';

        if (isset($pipes[1])) {
            fclose($pipes[1]);
        }
        if (isset($pipes[2])) {
            fclose($pipes[2]);
        }

        $exitCode = proc_close($process);

        return [
            'exit_code' => $exitCode,
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
        ];
    }

    private function sanitizeFilename(string $value): string
    {
        $clean = trim($value);
        if ($clean === '') {
            return 'borrow-request.pdf';
        }

        $name = Str::slug(pathinfo($clean, PATHINFO_FILENAME));
        $extension = strtolower(pathinfo($clean, PATHINFO_EXTENSION) ?: 'pdf');

        if ($name === '') {
            $name = 'borrow-request';
        }

        return "{$name}.{$extension}";
    }

    private function buildQrPayload(BorrowRequest $borrowRequest): string
    {
        if (Route::has('admin.borrow.requests.scan')) {
            try {
                return URL::temporarySignedRoute(
                    'admin.borrow.requests.scan',
                    now()->addDays(30),
                    ['borrowRequest' => $borrowRequest->id]
                );
            } catch (Throwable) {
                // fall back to plain identifier
            }
        }

        return (string) $borrowRequest->id;
    }
}
