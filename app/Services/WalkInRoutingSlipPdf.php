<?php

namespace App\Services;

use App\Models\WalkInRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class WalkInRoutingSlipPdf
{
    private const FONT_FAMILY = 'Times';
    private const FONT_SIZE = 10.0;
    private const BASELINE_OFFSET = 2.0;

    /**
     * Rectangles for each AcroForm field; some fields appear multiple times on the template.
     *
     * @var array<string, array<int, array{llx: float, lly: float, urx: float, ury: float}>>
     */
    private const FIELD_LAYOUT = RoutingSlipPdf::FIELD_LAYOUT;

    private string $templatePath;

    public function __construct(?string $templatePath = null, ?string $preparedTemplatePath = null)
    {
        $defaultPrepared = storage_path('app/templates/routing_slip.prepared.pdf');
        $defaultTemplate = public_path('pdf/routing_slip.pdf');

        $prepared = $preparedTemplatePath ?: $defaultPrepared;
        $resolved = $templatePath ?: $defaultTemplate;

        if ($prepared && is_file($prepared)) {
            $this->templatePath = $prepared;
        } else {
            $this->templatePath = $resolved;
        }
    }

    /**
     * @return array{filename: string, content: string}
     */
    public function render(WalkInRequest $walkIn, ?string $filename = null): array
    {
        if (! class_exists(Fpdi::class)) {
            throw new RuntimeException('setasign/fpdi is required to render the routing slip.');
        }

        if (! is_file($this->templatePath)) {
            throw new RuntimeException('Routing slip template not found at ' . $this->templatePath . '.');
        }

        $walkIn->loadMissing(['items.item', 'user']);

        $pdf = new Fpdi('P', 'pt');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        $pageCount = $pdf->setSourceFile($this->templatePath);
        if ($pageCount < 1) {
            throw new RuntimeException('The routing slip template does not contain any pages.');
        }

        $templateId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($templateId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height']);

        $requestCode = $this->formatRequestCode($walkIn);
        $borrowerName = $this->resolveBorrowerName($walkIn);
        $address = $this->resolveAddress($walkIn);
        $phone = trim((string) ($walkIn->contact_number ?? $walkIn->user?->phone ?? ''));
        [$descLine1, $descLine2] = $this->buildDescriptionLines($walkIn);
        $dateValue = $this->formatDate($walkIn->delivered_at ?? $walkIn->updated_at ?? Carbon::now());

        $this->writeField($pdf, $size, 'request_slip', $requestCode);
        $this->writeField($pdf, $size, 'bname_slip', $borrowerName);
        $this->writeField($pdf, $size, 'baddress_slip', $address);
        $this->writeField($pdf, $size, 'bphone_slip', $phone);
        $this->writeField($pdf, $size, 'bdescription1_slip', $descLine1);
        $this->writeField($pdf, $size, 'bdescription2_slip', $descLine2);
        $this->writeField($pdf, $size, 'date_slip', $dateValue, ['align' => 'C']);

        $binary = $pdf->Output('S');

        $outputName = $filename ?: sprintf('routing-slip-%s.pdf', Str::slug($requestCode ?: (string) $walkIn->id, '-'));

        return [
            'filename' => $this->sanitizeFilename($outputName),
            'content' => $binary,
        ];
    }

    private function writeField(Fpdi $pdf, array $pageSize, string $name, ?string $value, array $options = []): void
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return;
        }

        foreach (self::FIELD_LAYOUT[$name] ?? [] as $rect) {
            $this->writeText($pdf, $pageSize, $rect, $text, $options);
        }
    }

    /**
     * @param array{llx: float, lly: float, urx: float, ury: float} $rect
     */
    private function writeText(Fpdi $pdf, array $pageSize, array $rect, string $value, array $options = []): void
    {
        $paddingX = $options['padding_x'] ?? 4.0;
        $paddingY = $options['padding_y'] ?? 3.0;

        $fieldWidth = max($rect['urx'] - $rect['llx'], 4);
        $fieldHeight = max($rect['ury'] - $rect['lly'], 10);

        $width = max($fieldWidth - ($paddingX * 2), 4);
        $height = max($fieldHeight - ($paddingY * 2), 10);

        $x = $rect['llx'] + $paddingX;
        $top = $pageSize['height'] - $rect['ury'];

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont(self::FONT_FAMILY, '', self::FONT_SIZE);

        $lineHeight = $options['line_height'] ?? 12.0;
        $baselineShift = $options['baseline_offset'] ?? self::BASELINE_OFFSET;
        $y = $top + $fieldHeight - $paddingY - $lineHeight + $baselineShift;

        $pdf->SetXY($x, $y);
        $pdf->MultiCell($width, $lineHeight, $this->encode($value), 0, $options['align'] ?? 'L');
    }

    private function encode(string $value): string
    {
        $encoded = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);
        if ($encoded === false) {
            return preg_replace('/[^\x20-\x7E]/', '', $value) ?? $value;
        }

        return $encoded;
    }

    private function formatRequestCode(WalkInRequest $walkIn): string
    {
        $formatted = trim((string) ($walkIn->formatted_request_id ?? ''));
        if ($formatted !== '') {
            return $formatted;
        }

        $id = (int) $walkIn->id;
        return sprintf('WI-%04d', $id > 0 ? $id : 0);
    }

    private function resolveBorrowerName(WalkInRequest $walkIn): string
    {
        $name = trim((string) ($walkIn->borrower_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $user = $walkIn->user;
        if ($user) {
            $userName = trim((string) ($user->full_name ?? ''));
            if ($userName !== '') {
                return $userName;
            }

            $parts = array_filter([
                $user->first_name ?? null,
                $user->last_name ?? null,
            ]);

            if (! empty($parts)) {
                return trim(implode(' ', $parts));
            }

            return trim((string) ($user->name ?? ''));
        }

        return '';
    }

    private function resolveAddress(WalkInRequest $walkIn): string
    {
        $candidates = [
            $walkIn->address,
            $walkIn->user?->address,
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) ($candidate ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function buildDescriptionLines(WalkInRequest $walkIn): array
    {
        $items = $walkIn->items ?? collect();

        $labels = $items
            ->map(function ($item) {
                $name = trim((string) ($item->item->name ?? $item->name ?? 'Item'));
                $qty = max(0, (int) ($item->quantity ?? 0));
                return sprintf('%s (x%d)', $name === '' ? 'Item' : $name, $qty);
            })
            ->values();

        if ($labels->isEmpty()) {
            return ['', ''];
        }

        $line1 = $labels->take(2)->implode('; ');
        $line2 = $labels->slice(2)->implode('; ');

        return [$line1, $line2];
    }

    private function formatDate(?Carbon $date): string
    {
        if (! $date) {
            return '';
        }

        return $date->format('m/d/Y');
    }

    private function sanitizeFilename(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'routing-slip.pdf';
        }

        $value = str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '-', $value);
        $value = preg_replace('/\s+/', '-', $value) ?? $value;

        return $value === '' ? 'routing-slip.pdf' : $value;
    }
}
