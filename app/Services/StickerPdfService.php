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

        // A4 dimensions in points (210mm x 297mm)
        $a4Width = 595.28;
        $a4Height = 841.89;
        
        // Spacing between stickers (in points)
        $spacing = 10;
        
        // Calculate how many stickers fit per row and column
        $stickerWidth = $size['width'];
        $stickerHeight = $size['height'];
        
        $stickersPerRow = max(1, (int) floor(($a4Width + $spacing) / ($stickerWidth + $spacing)));
        $stickersPerCol = max(1, (int) floor(($a4Height + $spacing) / ($stickerHeight + $spacing)));
        $stickersPerPage = $stickersPerRow * $stickersPerCol;
        
        // Calculate starting position to center stickers on page
        $totalWidth = ($stickersPerRow * $stickerWidth) + (($stickersPerRow - 1) * $spacing);
        $totalHeight = ($stickersPerCol * $stickerHeight) + (($stickersPerCol - 1) * $spacing);
        $startX = max(0, ($a4Width - $totalWidth) / 2);
        $startY = max(0, ($a4Height - $totalHeight) / 2);
        
        $stickerIndex = 0;
        $totalPages = (int) ceil(count($stickers) / $stickersPerPage);
        
        for ($page = 0; $page < $totalPages; $page++) {
            // Add A4 page
            $pdf->AddPage('P', [$a4Width, $a4Height]);
            
            $stickerOnPage = 0;
            
            while ($stickerOnPage < $stickersPerPage && $stickerIndex < count($stickers)) {
                $row = (int) floor($stickerOnPage / $stickersPerRow);
                $col = $stickerOnPage % $stickersPerRow;
                
                $x = $startX + ($col * ($stickerWidth + $spacing));
                $y = $startY + ($row * ($stickerHeight + $spacing));
                
                $pdf->useTemplate($templateId, $x, $y, $stickerWidth, $stickerHeight);
                
                $payload = $stickers[$stickerIndex];
                
                foreach ($layout as $field => $rect) {
                    $value = Arr::get($payload, $field);
                    
                    // Handle signature field specifically - check for data:image or trim whitespace
                    if ($field === 'print_signature') {
                        // Skip if null, empty, or just whitespace
                        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
                            continue;
                        }
                        // If it's not a data URL, skip it
                        if (!is_string($value) || !str_starts_with($value, 'data:image')) {
                            continue;
                        }
                    } else {
                        // For other fields, skip if null or empty
                        if ($value === null || $value === '') {
                            continue;
                        }
                    }
                    
                    $width = max($rect['urx'] - $rect['llx'], 4);
                    $height = max($rect['ury'] - $rect['lly'], 10);
                    $fieldX = $x + $rect['llx'];
                    
                    // PDF coordinates are bottom-left, FPDF uses top-left origin.
                    $fieldY = $y + ($stickerHeight - $rect['ury']);
                    
                    if (is_string($value) && str_starts_with($value, 'data:image')) {
                        // Ensure minimum dimensions for image rendering
                        $imageHeight = max($rect['ury'] - $rect['lly'], 10);
                        $imageWidth = max($width, 10);
                        $this->renderImage($pdf, $value, $fieldX, $fieldY, $imageWidth, $imageHeight);
                        continue;
                    }
                    
                    $pdf->SetXY($fieldX, $fieldY);
                    $pdf->SetFont('Helvetica', '', $this->resolveFontSize($width, $height, (string) $value));
                    $pdf->SetTextColor(0, 0, 0);
                    $lineHeight = max(min($height, 14), 8);
                    $pdf->MultiCell($width, $lineHeight, (string) $value, 0, 'L');
                }
                
                $stickerIndex++;
                $stickerOnPage++;
            }
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
        
        // Rename temp file to have .png extension for better compatibility
        $tmpPng = $tmp . '.png';

        try {
            file_put_contents($tmpPng, $binary);
            $pdf->Image($tmpPng, $x, $y, $width, $height);
        } catch (\Throwable $e) {
            // Log errors silently - signature rendering is optional
            \Log::warning('Failed to render signature image', [
                'error' => $e->getMessage(),
            ]);
        } finally {
            @unlink($tmpPng);
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
