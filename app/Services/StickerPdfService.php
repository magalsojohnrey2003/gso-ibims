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
     * @return array{filename: string, content: string}
     */
    public function render(array $stickers, ?string $filename = null): array
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

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pageCount = $pdf->setSourceFile($this->templatePath);
        if ($pageCount < 1) {
            throw new RuntimeException('The sticker template does not contain any pages.');
        }

        $templateId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($templateId);

        // A4 portrait dimensions in points (72 DPI)
        $a4Width = 595.28;
        $a4Height = 841.89;

        // Spacing and border settings (in points)
        $borderWidth = 2; // Small border around each sticker
        $spacing = 10; // Small spacing between stickers
        $pageMargin = 15; // Margin from page edges

        // Calculate sticker dimensions with border and spacing
        $stickerWidth = $size['width'] + ($borderWidth * 2);
        $stickerHeight = $size['height'] + ($borderWidth * 2);

        // Calculate how many stickers fit per row and column
        $availableWidth = $a4Width - ($pageMargin * 2);
        $availableHeight = $a4Height - ($pageMargin * 2);

        // Calculate stickers per row (accounting for spacing)
        $stickersPerRow = max(1, floor(($availableWidth + $spacing) / ($stickerWidth + $spacing)));
        // Calculate stickers per column (accounting for spacing)
        $stickersPerColumn = max(1, floor(($availableHeight + $spacing) / ($stickerHeight + $spacing)));

        // Calculate actual sticker size to maximize space usage
        $totalStickersPerPage = $stickersPerRow * $stickersPerColumn;
        if ($totalStickersPerPage > 1) {
            // Calculate optimal dimensions
            $optimalWidth = ($availableWidth - ($spacing * ($stickersPerRow - 1))) / $stickersPerRow;
            $optimalHeight = ($availableHeight - ($spacing * ($stickersPerColumn - 1))) / $stickersPerColumn;

            // Maintain aspect ratio while maximizing space
            $templateAspectRatio = $size['width'] / $size['height'];
            $optimalAspectRatio = ($optimalWidth - ($borderWidth * 2)) / ($optimalHeight - ($borderWidth * 2));

            if ($optimalAspectRatio > $templateAspectRatio) {
                // Width is limiting factor
                $scaledWidth = $optimalWidth - ($borderWidth * 2);
                $scaledHeight = $scaledWidth / $templateAspectRatio;
            } else {
                // Height is limiting factor
                $scaledHeight = $optimalHeight - ($borderWidth * 2);
                $scaledWidth = $scaledHeight * $templateAspectRatio;
            }

            $scaleX = $scaledWidth / $size['width'];
            $scaleY = $scaledHeight / $size['height'];
        } else {
            // Single sticker per page - use original size
            $scaleX = 1.0;
            $scaleY = 1.0;
        }

        $scaledStickerWidth = ($size['width'] * $scaleX) + ($borderWidth * 2);
        $scaledStickerHeight = ($size['height'] * $scaleY) + ($borderWidth * 2);

        // Recalculate stickers per row/column with scaled dimensions
        $stickersPerRow = max(1, floor(($availableWidth + $spacing) / ($scaledStickerWidth + $spacing)));
        $stickersPerColumn = max(1, floor(($availableHeight + $spacing) / ($scaledStickerHeight + $spacing)));
        $totalStickersPerPage = $stickersPerRow * $stickersPerColumn;

        $currentPage = null;
        $stickersOnCurrentPage = 0;

        foreach ($stickers as $index => $payload) {
            // Start new page if needed
            if ($currentPage === null || $stickersOnCurrentPage >= $totalStickersPerPage) {
                $pdf->AddPage('P', [$a4Width, $a4Height]);
                $currentPage = $index;
                $stickersOnCurrentPage = 0;
            }

            // Calculate position for this sticker
            $row = floor($stickersOnCurrentPage / $stickersPerRow);
            $col = $stickersOnCurrentPage % $stickersPerRow;

            // Calculate X position (center if only one sticker per row)
            $xOffset = $pageMargin;
            if ($stickersPerRow > 1) {
                $totalRowWidth = ($scaledStickerWidth * $stickersPerRow) + ($spacing * ($stickersPerRow - 1));
                $xOffset = $pageMargin + (($availableWidth - $totalRowWidth) / 2) + ($col * ($scaledStickerWidth + $spacing));
            } else {
                // Center single sticker horizontally
                $xOffset = ($a4Width - $scaledStickerWidth) / 2;
            }

            // Calculate Y position
            $yOffset = $pageMargin;
            if ($stickersPerColumn > 1) {
                $totalColHeight = ($scaledStickerHeight * $stickersPerColumn) + ($spacing * ($stickersPerColumn - 1));
                $yOffset = $pageMargin + (($availableHeight - $totalColHeight) / 2) + ($row * ($scaledStickerHeight + $spacing));
            } else {
                // Center single sticker vertically
                $yOffset = ($a4Height - $scaledStickerHeight) / 2;
            }

            // Draw border around sticker
            $pdf->SetDrawColor(200, 200, 200); // Light gray border
            $pdf->SetLineWidth($borderWidth);
            $pdf->Rect($xOffset, $yOffset, $scaledStickerWidth, $scaledStickerHeight);

            // Calculate template position (centered within border)
            $templateX = $xOffset + $borderWidth;
            $templateY = $yOffset + $borderWidth;
            $templateWidth = $size['width'] * $scaleX;
            $templateHeight = $size['height'] * $scaleY;

            // Use template at scaled size
            $pdf->useTemplate($templateId, $templateX, $templateY, $templateWidth, $templateHeight, false);

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
                $adjustedY = $templateY + ($templateHeight - ($rect['ury'] * $scaleY));

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
