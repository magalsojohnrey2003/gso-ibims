<?php

namespace App\Services;

use Illuminate\Support\Arr;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\PdfReader\PdfReader;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfString;
use setasign\Fpdi\PdfParser\Type\PdfType;

class StickerPdfService
{
    private ?array $fieldLayout = null;

    public function __construct(private ?string $templatePath = null)
    {
        if ($this->templatePath === null) {
            $default = public_path('pdf/sticker_with_fields.pdf');
            $uncompressed = public_path('pdf/sticker_with_fields_uncompressed.pdf');
            $this->templatePath = is_file($uncompressed) ? $uncompressed : $default;
        }
    }

    /**
     * @param array<int, array<string, string|null>> $stickers
     * @param string|null $filename
     * @param string $orientation 'P' for portrait, 'L' for landscape
     * @return array{filename: string, content: string}
     */
    public function render(array $stickers, ?string $filename = null, string $orientation = 'P'): array
    {
        if (!class_exists(Fpdi::class)) {
            throw new RuntimeException('The setasign/fpdi package is required to generate sticker PDFs.');
        }

        if (!is_file($this->templatePath)) {
            throw new RuntimeException("PDF template not found at {$this->templatePath}.");
        }

        if ($stickers === []) {
            throw new RuntimeException('At least one sticker payload is required to render the PDF.');
        }

        $layout = $this->getFieldLayout();

        if ($layout === []) {
            throw new RuntimeException('No form fields could be detected in the sticker template.');
        }

        // Normalize orientation
        $orientation = strtoupper($orientation) === 'L' ? 'L' : 'P';
        
        $pdf = new Fpdi($orientation, 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pageCount = $pdf->setSourceFile($this->templatePath);
        if ($pageCount < 1) {
            throw new RuntimeException('The sticker template does not contain any pages.');
        }

        $templateId = $pdf->importPage(1);
        $templateSize = $pdf->getTemplateSize($templateId);
        $templateWidth = $templateSize['width'];
        $templateHeight = $templateSize['height'];
        $templateAspectRatio = $templateWidth / $templateHeight;

        // A4 dimensions in points (72 DPI)
        $a4Width = 595.28;
        $a4Height = 841.89;

        // Spacing and border settings (in points)
        $borderWidth = 2; // Small border around each sticker
        $spacing = 8; // Small spacing between stickers (matches example image)
        $pageMargin = 12; // Margin from page edges

        // Set page dimensions based on orientation
        $pageWidth = $orientation === 'L' ? $a4Height : $a4Width;
        $pageHeight = $orientation === 'L' ? $a4Width : $a4Height;

        // Available space after margins
        $availableWidth = $pageWidth - ($pageMargin * 2);
        $availableHeight = $pageHeight - ($pageMargin * 2);

        // Calculate optimal grid layout by trying different configurations
        $bestLayout = $this->calculateOptimalLayout(
            $templateWidth,
            $templateHeight,
            $availableWidth,
            $availableHeight,
            $spacing,
            $borderWidth
        );

        $stickersPerRow = $bestLayout['cols'];
        $stickersPerColumn = $bestLayout['rows'];
        $totalStickersPerPage = $stickersPerRow * $stickersPerColumn;
        $scaleX = $bestLayout['scaleX'];
        $scaleY = $bestLayout['scaleY'];

        // Calculate scaled dimensions
        $scaledStickerWidth = ($templateWidth * $scaleX) + ($borderWidth * 2);
        $scaledStickerHeight = ($templateHeight * $scaleY) + ($borderWidth * 2);

        $currentPage = null;
        $stickersOnCurrentPage = 0;

        foreach ($stickers as $index => $payload) {
            // Start new page if needed
            if ($currentPage === null || $stickersOnCurrentPage >= $totalStickersPerPage) {
                $pdf->AddPage($orientation, [$pageWidth, $pageHeight]);
                $currentPage = $index;
                $stickersOnCurrentPage = 0;
            }

            // Calculate position for this sticker
            $row = floor($stickersOnCurrentPage / $stickersPerRow);
            $col = $stickersOnCurrentPage % $stickersPerRow;

            // Calculate total width and height of the grid
            $totalGridWidth = ($scaledStickerWidth * $stickersPerRow) + ($spacing * ($stickersPerRow - 1));
            $totalGridHeight = ($scaledStickerHeight * $stickersPerColumn) + ($spacing * ($stickersPerColumn - 1));

            // Center the grid and calculate position
            $xOffset = $pageMargin + (($availableWidth - $totalGridWidth) / 2) + ($col * ($scaledStickerWidth + $spacing));
            $yOffset = $pageMargin + (($availableHeight - $totalGridHeight) / 2) + ($row * ($scaledStickerHeight + $spacing));

            // Draw border around sticker
            $pdf->SetDrawColor(200, 200, 200); // Light gray border
            $pdf->SetLineWidth($borderWidth);
            $pdf->Rect($xOffset, $yOffset, $scaledStickerWidth, $scaledStickerHeight);

            // Calculate template position (centered within border)
            $templateX = $xOffset + $borderWidth;
            $templateY = $yOffset + $borderWidth;
            $scaledTemplateWidth = $templateWidth * $scaleX;
            $scaledTemplateHeight = $templateHeight * $scaleY;

            // Use template at scaled size
            $pdf->useTemplate($templateId, $templateX, $templateY, $scaledTemplateWidth, $scaledTemplateHeight, false);

            // Render fields with scaling applied
            foreach ($layout as $field => $rect) {
                $value = Arr::get($payload, $field);
                
                // For signature field, check if it's a valid image data URL (not empty or just whitespace)
                if ($field === 'print_signature') {
                    if ($value === null || $value === '' || trim($value) === '' || !str_starts_with(trim($value), 'data:image')) {
                        continue;
                    }
                } else {
                    if ($value === null || $value === '' || trim($value) === '') {
                        continue;
                    }
                }

                // Apply scaling to field positions and dimensions
                $scaledX = $templateX + ($rect['llx'] * $scaleX);
                $scaledWidth = max(($rect['urx'] - $rect['llx']) * $scaleX, 4);
                $scaledHeight = max(($rect['ury'] - $rect['lly']) * $scaleY, 10);

                // Adjust Y coordinate (PDF coordinates are bottom-left, FPDF uses top-left)
                // Convert from PDF bottom-left to FPDF top-left coordinate system
                // The field's top edge (ury) in PDF coordinates becomes the position in FPDF coordinates
                $adjustedY = $templateY + ($scaledTemplateHeight - ($rect['ury'] * $scaleY));

                if (is_string($value) && str_starts_with(trim($value), 'data:image')) {
                    $this->renderImage($pdf, trim($value), $scaledX, $adjustedY, $scaledWidth, $scaledHeight);
                    continue;
                }

                $pdf->SetXY($scaledX, $adjustedY);
                $pdf->SetFont('Helvetica', '', $this->resolveFontSize($scaledWidth, $scaledHeight, (string) $value));
                $pdf->SetTextColor(0, 0, 0);
                $lineHeight = max(min($scaledHeight, 14), 8);
                $pdf->MultiCell($scaledWidth, $lineHeight, (string) $value, 0, 'L');
            }

            $stickersOnCurrentPage++;
        }

        $binary = $pdf->Output('S');

        return [
            'filename' => $filename ? $this->sanitizeFilename($filename) : 'stickers-' . now()->format('YmdHis') . '.pdf',
            'content' => $binary,
        ];
    }

    /**
     * Calculate the optimal grid layout that maximizes sticker size
     * 
     * @param float $templateWidth Original template width
     * @param float $templateHeight Original template height
     * @param float $availableWidth Available width for stickers
     * @param float $availableHeight Available height for stickers
     * @param float $spacing Spacing between stickers
     * @param float $borderWidth Border width around each sticker
     * @return array{rows: int, cols: int, scaleX: float, scaleY: float, stickerArea: float}
     */
    private function calculateOptimalLayout(
        float $templateWidth,
        float $templateHeight,
        float $availableWidth,
        float $availableHeight,
        float $spacing,
        float $borderWidth
    ): array {
        $bestLayout = null;
        $maxStickerArea = 0;

        // Try different grid configurations (rows x cols)
        // Test up to 5 columns and 5 rows
        $maxCols = 5;
        $maxRows = 5;

        for ($rows = 1; $rows <= $maxRows; $rows++) {
            for ($cols = 1; $cols <= $maxCols; $cols++) {
                // Calculate available space per sticker (accounting for spacing)
                $spacingWidth = $spacing * max(0, $cols - 1);
                $spacingHeight = $spacing * max(0, $rows - 1);
                
                $spacePerStickerWidth = ($availableWidth - $spacingWidth) / $cols;
                $spacePerStickerHeight = ($availableHeight - $spacingHeight) / $rows;

                // Account for borders
                $stickerContentWidth = max(0, $spacePerStickerWidth - ($borderWidth * 2));
                $stickerContentHeight = max(0, $spacePerStickerHeight - ($borderWidth * 2));

                if ($stickerContentWidth <= 0 || $stickerContentHeight <= 0) {
                    continue; // This layout doesn't fit
                }

                // Calculate scale factors while maintaining aspect ratio
                // Try scaling by width first
                $scaleByWidth = $stickerContentWidth / $templateWidth;
                $resultingHeight = $templateHeight * $scaleByWidth;
                
                if ($resultingHeight > $stickerContentHeight) {
                    // Scale by height instead
                    $scaleByHeight = $stickerContentHeight / $templateHeight;
                    $resultingWidth = $templateWidth * $scaleByHeight;
                    
                    if ($resultingWidth > $stickerContentWidth) {
                        continue; // This layout doesn't work
                    }
                    
                    $scaleX = $scaleY = $scaleByHeight;
                    $finalWidth = $templateWidth * $scaleX;
                    $finalHeight = $templateHeight * $scaleY;
                } else {
                    $scaleX = $scaleY = $scaleByWidth;
                    $finalWidth = $templateWidth * $scaleX;
                    $finalHeight = $templateHeight * $scaleY;
                }

                // Calculate sticker area
                $stickerArea = $finalWidth * $finalHeight;

                // Prefer this layout if it results in larger stickers
                if ($stickerArea > $maxStickerArea) {
                    $maxStickerArea = $stickerArea;
                    $bestLayout = [
                        'rows' => $rows,
                        'cols' => $cols,
                        'scaleX' => $scaleX,
                        'scaleY' => $scaleY,
                        'stickerArea' => $stickerArea,
                    ];
                }
            }
        }

        // Fallback to single sticker if no layout found
        if ($bestLayout === null) {
            // Calculate scale for single sticker
            $stickerContentWidth = $availableWidth - ($borderWidth * 2);
            $stickerContentHeight = $availableHeight - ($borderWidth * 2);
            
            // Maintain aspect ratio
            $scaleByWidth = $stickerContentWidth / $templateWidth;
            $scaleByHeight = $stickerContentHeight / $templateHeight;
            $scale = min($scaleByWidth, $scaleByHeight);

            $bestLayout = [
                'rows' => 1,
                'cols' => 1,
                'scaleX' => $scale,
                'scaleY' => $scale,
                'stickerArea' => ($templateWidth * $scale) * ($templateHeight * $scale),
            ];
        }

        return $bestLayout;
    }

    private function renderImage(Fpdi $pdf, string $dataUrl, float $x, float $y, float $width, float $height): void
    {
        if (!str_contains($dataUrl, ',')) {
            return;
        }

        [$meta, $encoded] = explode(',', $dataUrl, 2);
        if ($encoded === '' || !str_contains($meta, 'image')) {
            return;
        }

        $binary = base64_decode($encoded);
        if ($binary === false) {
            return;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'sig');
        if ($tmp === false) {
            return;
        }

        try {
            file_put_contents($tmp, $binary);
            $pdf->Image($tmp, $x, $y, $width, $height);
        } catch (\Throwable) {
            // ignore rendering issues and continue
        } finally {
            @unlink($tmp);
        }
    }

    private function sanitizeFilename(string $filename): string
    {
        $clean = preg_replace('/[^\w\-.]+/', '_', $filename) ?? 'stickers.pdf';
        return $clean !== '' ? $clean : 'stickers.pdf';
    }

    /**
     * @return array<string, array{llx: float, lly: float, urx: float, ury: float}>
     */
    private function getFieldLayout(): array
    {
        if ($this->fieldLayout !== null) {
            return $this->fieldLayout;
        }

        $this->fieldLayout = [];

        if (!class_exists(PdfReader::class)) {
            return $this->fieldLayout;
        }

        try {
            $streamReader = StreamReader::createByFile($this->templatePath);
            $parser = new PdfParser($streamReader);
            new PdfReader($parser); // initialize reader to ensure compatibility

            $catalog = PdfDictionary::ensure($parser->getCatalog());

            if (!isset($catalog->value['AcroForm'])) {
                return $this->fieldLayout;
            }

            $acroForm = PdfDictionary::ensure(
                PdfType::resolve($catalog->value['AcroForm'], $parser)
            );

            if (!isset($acroForm->value['Fields'])) {
                return $this->fieldLayout;
            }

            $fields = PdfArray::ensure(
                PdfType::resolve($acroForm->value['Fields'], $parser)
            )->value;

            foreach ($fields as $fieldRef) {
                try {
                    $this->collectFieldLayout($parser, $fieldRef);
                } catch (\Throwable) {
                    continue;
                }
            }
        } catch (\Throwable) {
            // Leave layout empty so we can bubble up a meaningful error later.
        }

        return $this->fieldLayout;
    }

    private function collectFieldLayout($parser, $fieldObject, string $parentName = ''): void
    {
        $field = PdfDictionary::ensure(PdfType::resolve($fieldObject, $parser));
        $values = $field->value;

        $name = $parentName;

        if (isset($values['T'])) {
            $resolvedName = PdfType::resolve($values['T'], $parser);
            if ($resolvedName instanceof PdfString || $resolvedName instanceof PdfName) {
                $rawName = (string) $resolvedName->value;
                $name = $parentName !== '' ? $parentName . '.' . $rawName : $rawName;
            }
        }

        if (isset($values['Kids'])) {
            $kids = PdfArray::ensure(PdfType::resolve($values['Kids'], $parser))->value;
            foreach ($kids as $kid) {
                try {
                    $this->collectFieldLayout($parser, $kid, $name);
                } catch (\Throwable) {
                    continue;
                }
            }
            return;
        }

        if ($name === '' || !isset($values['Rect'])) {
            return;
        }

        $rect = $this->resolveRect($parser, PdfArray::ensure(PdfType::resolve($values['Rect'], $parser))->value);
        if ($rect !== null) {
            $current = $this->fieldLayout[$name] ?? null;
            if ($current !== null) {
                $currentArea = ($current['urx'] - $current['llx']) * ($current['ury'] - $current['lly']);
                $newArea = ($rect['urx'] - $rect['llx']) * ($rect['ury'] - $rect['lly']);

                if ($newArea >= $currentArea) {
                    return;
                }
            }

            $this->fieldLayout[$name] = $rect;
        }
    }

    /**
     * @param array<int, mixed> $items
     * @return array{llx: float, lly: float, urx: float, ury: float}|null
     */
    private function resolveRect($parser, array $items): ?array
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

    private function resolveFontSize(float $width, float $height, string $value): float
    {
        $trimmed = trim($value);
        $length = max($trimmed !== '' ? mb_strlen($trimmed, 'UTF-8') : 0, 1);
        $estimated = $width / ($length * 0.6);

        $fontSize = max(min($estimated, $height), 8.0);

        return $fontSize;
    }
}
